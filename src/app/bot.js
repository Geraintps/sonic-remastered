// prepare discord.js
const { Client, ActionRowBuilder, ButtonBuilder, ButtonStyle, Util, GatewayIntentBits } = require("discord.js");
const client = new Client({
    intents: [
		GatewayIntentBits.Guilds
		, GatewayIntentBits.GuildVoiceStates
		, GatewayIntentBits.GuildMessages
		, GatewayIntentBits.GuildMessageReactions
		, GatewayIntentBits.GuildMessageTyping
	]
});

// get the required modules
const Bot = require('./Modules/Bot.class');
const Command = require('./Modules/Command.class');

// prepare the bot
const bot = new Bot({}, client);

process.on('unhandledRejection', error => {
	console.error('Unhandled promise rejection:', error);
});