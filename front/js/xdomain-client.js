/**
 * Cross-Domain Communication Client
 * Provides communication with the backend when CORS is not functioning properly
 */
class XDomainClient {
	constructor(
		backendUrl = "https://app-backend-esgi-app.azurewebsites.net"
	) {
		this.backendUrl = backendUrl;
		this.bridgePath = "/xdomain.html";
		this.iframe = null;
		this.isReady = false;
		this.callbacks = {};
		this.requestId = 0;
		this.timeout = 30000; // 30 seconds timeout

		// Setup message listener
		window.addEventListener(
			"message",
			this.handleMessage.bind(this)
		);
	}

	/**
	 * Initialize the communication bridge
	 * @returns {Promise} Promise that resolves when the bridge is ready
	 */
	async init() {
		if (this.iframe) {
			return Promise.resolve();
		}

		return new Promise((resolve, reject) => {
			// Create a hidden iframe
			this.iframe = document.createElement("iframe");
			this.iframe.style.display = "none";
			this.iframe.src = this.backendUrl + this.bridgePath;

			// Setup loading handlers
			this.iframe.onload = () => {
				console.log("XDomain bridge loaded");
				this.isReady = true;
				resolve();
			};

			this.iframe.onerror = (error) => {
				console.error(
					"Failed to load XDomain bridge:",
					error
				);
				reject(
					new Error(
						"Failed to load cross-domain communication bridge"
					)
				);
			};

			// Add to document
			document.body.appendChild(this.iframe);

			// Set a timeout
			setTimeout(() => {
				if (!this.isReady) {
					reject(
						new Error(
							"Timeout loading cross-domain bridge"
						)
					);
				}
			}, this.timeout);
		});
	}

	/**
	 * Handle incoming messages from the iframe
	 */
	handleMessage(event) {
		// Only accept messages from our bridge
		if (event.origin !== this.backendUrl) {
			return;
		}

		const response = event.data;
		console.log("Received XDomain response:", response);

		// Check if we have a callback for this response
		if (response.id && this.callbacks[response.id]) {
			// Call the callback with the response data
			this.callbacks[response.id](response);

			// Clean up the callback
			delete this.callbacks[response.id];
		}
	}

	/**
	 * Send a request to the backend via the bridge
	 * @param {Object} data - The data to send
	 * @param {number} timeout - Timeout in milliseconds
	 * @returns {Promise} Promise that resolves with the response
	 */
	async sendRequest(data, timeout = this.timeout) {
		// Make sure the bridge is initialized
		if (!this.isReady) {
			await this.init();
		}

		return new Promise((resolve, reject) => {
			// Generate a unique ID for this request
			const requestId = ++this.requestId;

			// Create the full request
			const request = {
				...data,
				id: requestId,
				timestamp: new Date().toISOString()
			};

			// Store the callback
			this.callbacks[requestId] = (response) => {
				if (response.success) {
					resolve(response);
				} else {
					reject(
						new Error(
							response.error ||
								"Unknown error"
						)
					);
				}
			};

			// Set a timeout
			const timeoutId = setTimeout(() => {
				if (this.callbacks[requestId]) {
					delete this.callbacks[requestId];
					reject(new Error("Request timed out"));
				}
			}, timeout);

			// Send the request to the iframe
			this.iframe.contentWindow.postMessage(
				request,
				this.backendUrl
			);

			console.log("Sent XDomain request:", request);
		});
	}

	/**
	 * Simple ping test to verify communication
	 * @returns {Promise} Promise that resolves with the ping response
	 */
	async ping() {
		return this.sendRequest({ type: "ping" });
	}

	/**
	 * Clean up resources
	 */
	destroy() {
		if (this.iframe) {
			document.body.removeChild(this.iframe);
			this.iframe = null;
		}

		this.isReady = false;
		this.callbacks = {};
		window.removeEventListener("message", this.handleMessage);
	}
}

// Create a global instance
window.xdomainClient = new XDomainClient();
