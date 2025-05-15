<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <defaultDocument>
            <files>
                <clear />
                <add value="info.php" />
                <add value="index.php" />
                <add value="default.php" />
            </files>
        </defaultDocument>
        <rewrite>
            <rules>
                <rule name="Redirect root to info.php" stopProcessing="true">
                    <match url="^$" />
                    <action type="Redirect" url="info.php" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
