// prepare axios
const axios = require('axios');

// prepare encryption
const crypto = require('crypto');
const algorithm = 'aes-256-cbc';
require('dotenv').config();
const key = Buffer.from(process.env.ENCRYPTION_KEY, 'hex');
const isEmpty = (obj) => Object.keys(obj).length === 0;

class BotCore {
	#postData = {};

	constructor(postLink, settings = {}, client) {
		this.setPostLink(postLink);
		this.settings = settings;
		this.client = client;
		this.commands;
		this.setCommandsFromSettings();
	}

	setPostLink(link) {
		this.postLink = link;
	}

	getPostLink() {
		return this.postLink;
	}

	setPostData(data) {
		this.#postData = data;
	}

	setCommandsFromSettings() {

		// check we have settings
		if(!isEmpty(this.settings)) {
			this.commands = this.settings.commands;
		}
	}

	async getSettings() {

		// check if settings is empty
		if(isEmpty(this.settings)) {

			// prepare the post data
			this.setPostData({
				action: 'getSettings'
			});

			try {

				// get the settings
				var data = await this.postData();
				if(data.success
					&& data.settings
				) {
					this.settings = data.settings;
					this.commands = data.commands;
					this.settings.success = true;

					// append any commands to the settings
					if(data.commands) {
						this.settings.commands = data.commands;
					}
				} else {

					// set the error
					this.setError('No settings found');

					this.settings = {
						success: false,
						message: 'No settings found'
					};
				}
			} catch (error) {

				// log the error
				console.log(error);

				// return error
				this.settings = {
					success: false,
					message: 'Something went wrong...'
				};
			}
		}

		// return the settings
		return this.settings;
	}

	async postData() {

		// check we have some data and that it's an object
		if(isEmpty(this.#postData)
			|| typeof this.#postData != 'object'
		) {

			// set the error
			this.setError('No data provided');

			// return error
			return {
				success: false,
				message: 'Something went wrong...'
			};
		} else {

			// encrypt the data
			const jsonData = JSON.stringify(this.#postData);
			const encryptedData = this.encrypt(jsonData);

			// try to post the data
			try {

				// post the data
				const response = await axios.post(this.postLink, encryptedData);
				const decrypted = this.decrypt(response.data.encryptedData, response.data.iv);
				return decrypted;
			} catch (error) {

				// log the error
				this.setError(error.message);

				// return error
				return {
					success: false,
					message: 'Something went wrong...'
				};
			}
		}
	}

	encrypt(text) {
		const iv = crypto.randomBytes(16);
		const cipher = crypto.createCipheriv(algorithm, key, iv);
		let encrypted = cipher.update(text, 'utf8', 'base64');
		encrypted += cipher.final('base64');
		return {
			iv: iv.toString('base64'),
			encryptedData: encrypted
		};
	}

	decrypt(encryptedData, iv) {
		const decipher = crypto.createDecipheriv(algorithm, key, Buffer.from(iv, 'base64'));
		let decrypted = decipher.update(encryptedData, 'base64', 'utf8');
		decrypted += decipher.final('utf8');
		return JSON.parse(decrypted);
	}

	setError(message, stackTrace = "") {
		const date = new Date();
		const timestamp = date.toLocaleString('en-GB', { hour12: false });
		const errorMessage = message || 'Unknown error';

		// check for a stack trace
		if(stackTrace == "") {
			stackTrace = new Error().stack;
		}

		// format the log
		const formattedLog = `[${timestamp}] ERROR:\n${errorMessage}\nStack: ${stackTrace}`;

		// log the error
		console.error(formattedLog);
	}

	setOutput(message) {
		const date = new Date();
		const timestamp = date.toLocaleString('en-GB', { hour12: false });

		// check if the message is an object or an array
		if(typeof message == 'object'
			|| Array.isArray(message)
		) {
			message = JSON.stringify(message);
		}

		// check the message is now a string
		if(typeof message != 'string') {
			message = 'Unknown output';
		}

		// format the log
		const formattedLog = `[${timestamp}]\n${message}`;

		// log the output
		console.log(formattedLog);
	}
}

// export the module
module.exports = BotCore;