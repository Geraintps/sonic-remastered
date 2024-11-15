const BotCore = require("./BotCore.class");

class Command extends BotCore {
	constructor(postLink, settings = {}, client, command = '') {
		super(postLink, settings, client);
		this.command = command;
		this.options = {};
		this.user;
		this.active = false;
		this.module = '';
		this.modules = {};
		this.response = {};
		this.checkActive();
		this.checkModule();
	}

	setCommand(command) {
		this.command = command;
		this.checkActive();
		this.checkModule();
	}

	setCommandOptions(options) {

		// check we have options
		if(options) {

			// loop through the options
			options.forEach(option => {

				// check we have a name
				if(option.name) {

					// get the name
					const name = option.name;

					// set the option
					this.options[name] = option.value;
				}
			});
		}
	}

	checkActive() {

		// check if this command is in the list of commands
		Object.keys(this.commands).forEach(key => {

			// get the command
			var item = this.commands[key];

			// check if this is the command we are looking for
			if(item.name == this.command) {
				this.active = true;
			}
		});
	}

	checkModule() {

		// check if this command is in the list of commands
		Object.keys(this.commands).forEach(key => {

			// get the command
			var item = this.commands[key];

			// check if this is the command we are looking for
			if(item.name == this.command) {

				// check if we have a module
				if(item.module
					&& item.module != ''
					&& item.module != 'null'
				) {
					this.module = item.module;
				}
			}
		});
	}

	setCommand(command) {
		this.command = command;
	}

	setCommandUser(user) {
		this.user = user;
	}

	setResponse(response) {
		this.response = response;
	}

	getResponse() {
		return this.response;
	}

	async run() {

		// check we have a command
		if(this.command != ''
			&& this.active
		) {
			try {

				// capitalize the first letter
				var name = this.command.charAt(0).toUpperCase() + this.command.slice(1);

				// prepare the post data
				this.setPostData({
					action: 'runCommand',
					command: name,
					options: this.options
				});

				// get the response
				const data = await this.postData();

				// check if we have a success response
				if(data.success) {

					// check if we should load a module
					if(this.module != '') {

						// initialize the module
						var Module = require(`./${this.module}.class`);
						var module;

						// check if we've already loaded the module
						if(this.modules[this.module]) {
							module = this.modules[this.module];
						} else {
							module = new Module(this.postLink, this.settings, this.client, this.command);
							this.modules[this.module] = module;
						}

						// set the action, options, and user
						module.setAction(this.command);
						module.setOptions(this.options);
						module.setUser(this.user);
						await module.run();
						this.setResponse(module.getResponse());
					} else {
						this.setResponse({
							success: data.success,
							message: data.message
						});
					}
				} else {
					this.setError(data.message);
					this.setResponse({
						success: data.success,
						message: data.message
					});
				}
			} catch (error) {
				this.setError(error.message, error.stack);
				this.setResponse({
					success: false,
					message: 'Something went wrong...'
				});
			}
		} else if (!this.active) {
			this.setError(`Command not active: ${this.command}`);
			this.setResponse({
				success: false,
				message: 'This command is no longer active'
			});
		} else {
			this.setError('No command provided');
			this.setResponse({
				success: false,
				message: 'No command provided'
			});
		}
	}
}

// export the module
module.exports = Command;