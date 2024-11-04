// prepare discord.js
const { Client, ActionRowBuilder, ButtonBuilder, ButtonStyle, Util, GatewayIntentBits } = require("discord.js");
const client = new Client({
    intents: [
		GatewayIntentBits.Guilds
	]
});

// prepare the post link
require('dotenv').config();
const postLink = process.env.POST_LINK;

// get the required modules
const Bot = require('./Modules/Bot.class');
const Command = require('./Modules/Command.class');

// prepare the bot
const bot = new Bot(postLink, {}, client);