const BotCore = require("./BotCore.class");

class Command extends BotCore {
	constructor(postLink, settings = {}, client, command = '') {
		super(postLink, settings, client);
		this.command = command;
		this.active = false;
		this.checkActive();
		this.response = {};
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

	setCommand(command) {
		this.command = command;
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
				});

				// get the response
				this.response = await this.postData();
			} catch (error) {
				this.setError(error.message, error.stack);
				this.response = {
					success: false,
					message: 'Something went wrong...'
				};
			}
		} else if (!this.active) {
			this.setError(`Command not active: ${this.command}`);
			this.response = {
				success: false,
				message: 'This command is no longer active'
			};
		} else {
			this.setError('No command provided');
			this.response = {
				success: false,
				message: 'No command provided'
			};
		}
	}
}

// export the module
module.exports = Command;