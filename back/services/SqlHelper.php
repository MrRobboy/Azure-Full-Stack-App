<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Classe utilitaire pour gérer les différences de syntaxe SQL entre les différents SGBD
 */
class SqlHelper
{
	/**
	 * Retourne la fonction SQL appropriée pour obtenir la date et l'heure actuelles
	 * 
	 * @return string Fonction SQL pour la date/heure actuelle
	 */
	public static function getCurrentDateTimeFunction()
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
			return 'GETDATE()';
		} else {
			return 'NOW()';
		}
	}

	/**
	 * Retourne la fonction SQL appropriée pour formater une date
	 * 
	 * @param string $dateField Le champ ou expression de date à formater
	 * @param string $format Le format souhaité (format MySQL par défaut)
	 * @return string Fonction SQL pour formater la date
	 */
	public static function getDateFormatFunction($dateField, $format = '%Y-%m-%d')
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
			// Convertir le format MySQL en format SQL Server
			$sqlServerFormat = str_replace(
				['%Y', '%m', '%d', '%H', '%i', '%s'],
				['yyyy', 'MM', 'dd', 'HH', 'mm', 'ss'],
				$format
			);
			return "FORMAT($dateField, '$sqlServerFormat')";
		} else {
			return "DATE_FORMAT($dateField, '$format')";
		}
	}

	/**
	 * Retourne la clause LIMIT adaptée au SGBD utilisé
	 * 
	 * @param int $limit Nombre d'enregistrements à retourner
	 * @param int $offset Position de départ (0 par défaut)
	 * @return string Clause SQL pour limiter les résultats
	 */
	public static function getLimitClause($limit, $offset = 0)
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
			if ($offset > 0) {
				return "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
			} else {
				return "OFFSET 0 ROWS FETCH NEXT $limit ROWS ONLY";
			}
		} else {
			return "LIMIT $offset, $limit";
		}
	}

	/**
	 * Retourne l'instruction SQL pour gérer l'autoincrement selon le SGBD
	 * 
	 * @param string $tableName Nom de la table
	 * @param string $idColumn Nom de la colonne ID
	 * @return string Instruction SQL pour récupérer la dernière valeur d'ID insérée
	 */
	public static function getLastInsertIdQuery($tableName, $idColumn)
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
			return "SELECT SCOPE_IDENTITY() AS last_id";
		} else {
			return "SELECT LAST_INSERT_ID() AS last_id";
		}
	}

	/**
	 * Adapte une requête SQL pour être compatible avec le SGBD utilisé
	 * 
	 * @param string $query Requête SQL originale (format MySQL)
	 * @return string Requête SQL adaptée au SGBD actuel
	 */
	public static function adaptQuery($query)
	{
		if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
			// Remplacer les fonctions de date
			$query = str_replace('NOW()', 'GETDATE()', $query);

			// Remplacer les LIMIT x, y par OFFSET/FETCH
			if (preg_match('/LIMIT\s+(\d+)(?:\s*,\s*(\d+))?/i', $query, $matches)) {
				$limitClause = $matches[0];
				if (isset($matches[2])) {
					// LIMIT offset, limit
					$offset = $matches[1];
					$limit = $matches[2];
					$replacement = "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
				} else {
					// LIMIT limit
					$limit = $matches[1];
					$replacement = "OFFSET 0 ROWS FETCH NEXT $limit ROWS ONLY";
				}
				$query = str_replace($limitClause, $replacement, $query);
			}
		}

		return $query;
	}
}
