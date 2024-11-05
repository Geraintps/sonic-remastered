const BotCore = require("./BotCore.class");
const Command = require('./Command.class');
const { SlashCommandBuilder } = require('@discordjs/builders');
const { REST } = require('@discordjs/rest');
const { Routes } = require('discord-api-types/v9');
const isEmpty = (obj) => Object.keys(obj).length === 0;

class Bot extends BotCore {

	constructor(postLink, settings = {}, client) {
		super(postLink, settings, client);
		this.setup();
	}

	async setup() {

		// get the settings
		await this.getSettings();

		// check we have settings
		if(!this.settings
			|| isEmpty(this.settings)
			|| !this.settings.success
		) {
			this.setError('No settings found');
			return;
		}

		// login
		this.client.login(this.settings.clientKey);

		// on ready
		this.client.once("ready", this.onReady.bind(this));

		// on interaction
		this.client.on("interactionCreate", this.onInteraction.bind(this));

		// when client is added to a new server
		this.client.on('guildCreate', this.onGuildCreate.bind(this));

		// when client is removed from a server
		this.client.on('guildDelete', (guild) => {
			this.setOutput(`Bot has been removed from the guild ${guild.name} (${guild.id}`);
		});
	}

	async onGuildCreate(guild) {
        try {

            // register commands for the new guild
			this.setOutput(`Bot has been added to a new guild ${guild.name} (${guild.id}`);

            await this.registerCommands(guild);
        } catch (error) {
            this.setError(error.message);
        }
    }

	async onReady() {

		// check we have settings
		if(!this.settings
			|| isEmpty(this.settings)
			|| !this.settings.success
		) {
			this.setError('No settings found');
			return;
		}

		// build the commands
		await this.buildCommands();

		// bot is ready
		this.setOutput('Bot is ready');
	}

	async onInteraction(interaction) {

		// get the command name
		const commandName = interaction.commandName;

		// get the submitted options
		const options = interaction.options.data;

		// get the user
		const user = interaction.user;
		const username = String(interaction.member.user.username);
        const userid = String(interaction.member.id);

		// call the command module
		var command = new Command(this.postLink, this.settings, this.client, commandName);
		command.setOptions(options);
		await command.run();

		// get the response
		const response = command.getResponse();

		// check we have a message
		if(!response.message) {
			response.message = "I'm speechless...";
		}

		// check for errors
		if(response.success) {
			await interaction.reply(response.message);
		} else {
			await interaction.reply({ content: response.message, ephemeral: true });
		}
	}

	async buildCommands() {

		// check we have commands
		if(!this.commands
			|| isEmpty(this.commands)
		) {
			this.setError('No commands found');
			return false;
		}

		// register the commands
		try {
			const promises = this.client.guilds.cache.map(guild => this.registerCommands(guild));
			await Promise.all(promises);
			return true;
		} catch (error) {
			this.setError(error.message);
		}
		return false;
	}

	async registerCommands(guild) {

		// check we have commands
		if(!this.commands
			|| isEmpty(this.commands)
		) {
			this.setError('No commands found when registering commands');
			return;
		}

		// prepare the commands array
		var commands = [];

		// loop through the commands
		Object.keys(this.commands).forEach(key => {

			// get the command
			var command = this.commands[key];

			// check we have a name
			if(!command.name
				|| !command.description
			) {
				this.setError(['Missing command name or description', command]);
				return;
			}

			// prepare the command
			var cmd = new SlashCommandBuilder().setName(command.name).setDescription(command.description);

			// check for any options
			if(command.options) {
				Object.keys(command.options).forEach(key => {
					var opt = command.options[key];
					var required = opt.required ? true : false;
					cmd.addStringOption(option => option.setName(opt.name).setDescription(opt.description).setRequired(required));
				});
			}

			// add the command to the commands array
			commands.push(cmd.toJSON());
		});

		// register the commands
		const rest = new REST({ version: '9' }).setToken(this.settings.clientKey);
		try {
			await rest.put(Routes.applicationGuildCommands(this.settings.clientId, guild.id), { body: commands });
			this.setOutput(`Successfully registered application commands for guild ${guild.name} (${guild.id})`);
			return true;
		} catch (error) {
			this.setError(error.message);
			return false;
		}
	}
}

// export the module
module.exports = Bot;