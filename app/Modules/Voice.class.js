const Command = require("./Command.class");
const { joinVoiceChannel, createAudioPlayer, createAudioResource, AudioPlayerStatus, StreamType } = require('@discordjs/voice');
const ytdl = require('@distube/ytdl-core');
const playdl = require('play-dl');
const ytSearch = require('yt-search');
const fs = require('fs');

class Voice extends Command {
	constructor(postLink, settings = {}, client, command) {
		super(postLink, settings, client, command);
		this.queue = [];
		this.connection = null;
		this.player = createAudioPlayer();
		this.user;
		this.channel = null;
		this.isPlaying = false;
		this.action;
		this.options;
		this.response = {};

		// event listener for when the current track finishes playing
		this.player.on(AudioPlayerStatus.Idle, () => {
			this.setOutput('Track finished playing');
			this.isPlaying = false;
			this.queue.shift();
			if (this.queue.length > 0) {
				this.playNext();
			} else {
				// this.disconnect();
			}
		});

		// event listener for errors
		this.player.on('error', error => {
			this.setError(error.message, error.stack);
			this.isPlaying = false;
			this.queue.shift();
			if (this.queue.length > 0) {
				this.playNext();
			} else {
				this.disconnect();
			}
		});

		this.player.on('stateChange', (oldState, newState) => {
			this.setOutput(`Audio player state changed from ${oldState.status} to ${newState.status}`);
		});
	}

	#setResponse(success, message) {
		this.response = {
			success: success,
			message: message
		};
	}

	getResponse() {
		return this.response;
	}

	setAction(action) {
		this.action = action;
	}

	setOptions(options) {
		this.options = options;
	}

	setUser(user) {
		this.user = user;
	}

	async run() {

		// check we have an action
		if (this.action != '') {
			try {
				switch (this.action) {
					case 'play':
						await this.addToQueue();
						break;
					case 'skip':
						this.skip();
						break;
					case 'stop':
						this.stop();
						break;
					case 'queue':
						this.viewQueue();
						break;
					default:
						this.#setResponse(0, `Action not found: ${this.action}`);
						this.setError(`Action not found: ${this.action}`);
				}
			} catch (error) {
				this.setError(error.message, error.stack);
			}
		} else {
			this.setError('No action provided');
			this.#setResponse(0, `Something went wrong...`);
		}
	}

	async addToQueue() {

		// check if the song option is set
		if (!this.options
			|| !this.options.song
		) {
			this.setError('No song provided');
			this.#setResponse(0, `You need to provide a song to play.`);
			return;
		}

		// set the song
		const songRequest = this.options.song;

		// set the song url
		let videoUrl = "";

		// // validate YouTube URL
		// if (!ytdl.validateURL(songRequest)) {

		// 	// treat the input as a search query
		// 	const searchResult = await this.searchYouTube(songRequest);

		// 	// check if we have a search result
		// 	if (searchResult) {
		// 		videoUrl = searchResult.url;
		// 	} else {
		// 		this.#setResponse(1, `No results found for **"${songRequest}"**.`);
		// 		return;
		// 	}
		// } else {
		// 	videoUrl = songRequest;
		// }

		// get the voice channel of the user
		const voiceChannel = this.user.voice.channel;
		if (!voiceChannel) {
			this.#setResponse(0, `You need to be in a voice channel to play music!`);
			return;
		}

		// connect to the voice channel if not already connected
		if (!this.connection) {
			await this.connect(voiceChannel);
		} else if (this.channel.id !== voiceChannel.id) {

			// switch to the user's voice channel if connected elsewhere
			await this.disconnect();
			await this.connect(voiceChannel);
		}

		await playdl.getFreeClientID().then((clientID) => playdl.setToken({
			soundcloud : {
				client_id : clientID
			}
		}));

		// fetch song info
		// const songInfo = await ytdl.getInfo(videoUrl);
		// const songInfo = await playdl.video_basic_info(videoUrl);
		const track = await playdl.search(songRequest, { source : { soundcloud : "tracks" } });

		// check we have a song
		if (!track || track.length === 0) {
			this.#setResponse(1, `No results found for **"${songRequest}"**.`);
		} else {
			const song = {
				title: track[0].name,
				url: track[0].url,
			};

			// add the song to the queue
			this.queue.push(song);
			this.#setResponse(1, `**${song.title}** has been added to the queue!`);

			// start playing if not already
			if (!this.isPlaying) {
				await this.playNext();
			}
		}
	}

	async playNext() {
		if (this.queue.length === 0) {
			this.#setResponse(1, 'The queue is empty.');
			return;
		}

		const song = this.queue[0];
		this.isPlaying = true;

		const stream = await playdl.stream(song.url, {
			quality: 2, // 0 = low, 1 = medium, 2 = high
			discordPlayerCompatibility: true,
		});

		stream.stream.on('error', error => {
			this.setError(error);
		});

		const resource = createAudioResource(stream.stream, {
			inputType: stream.type
		});

		// const resource = createAudioResource(fs.createReadStream('./bins.mp3'), {
		// 	inputType: StreamType.Arbitrary, // Or StreamType.Raw if Arbitrary doesn't work
		// });

		this.player.play(resource);
		this.connection.subscribe(this.player);

		this.#setResponse(1, `Now playing: **${song.title}**`);
	}

	async searchYouTube(query) {
		try {
			const result = await ytSearch(query);
			if (result && result.videos && result.videos.length > 0) {
				return result.videos[0]; // return the first search result
			} else {
				return null;
			}
		} catch (error) {
			this.setError(error.message, error.stack);
			return null;
		}
	}

	async connect(voiceChannel) {

		// check bot permissions
		const permissions = voiceChannel.permissionsFor(this.client.user);
		if (!permissions.has('CONNECT') || !permissions.has('SPEAK')) {
			this.#setResponse(0, 'I need permissions to join and speak in your voice channel!');
			return;
		}

		// join the voice channel
		this.connection = joinVoiceChannel({
			channelId: voiceChannel.id,
			guildId: voiceChannel.guild.id,
			adapterCreator: voiceChannel.guild.voiceAdapterCreator,
			selfDeaf: false
		});

		this.channel = voiceChannel;
		this.#setResponse(1, `Joined **${voiceChannel.name}**!`);
	}

	async disconnect() {
		if (this.connection) {
			this.connection.destroy();
			this.connection = null;
			this.channel = null;
			this.isPlaying = false;
			this.#setResponse(1, 'Disconnected from the voice channel.');
		}
	}

	skip() {
		if (this.queue.length > 1) {
			this.player.stop();
			this.#setResponse(1, 'Skipped the current song.');
		} else {
			this.player.stop();
			this.#setResponse(1, 'Skipped the current song. The queue is now empty.');
		}
	}

	stop() {
		this.queue = [];
		this.player.stop();
		this.disconnect();
		this.#setResponse(1, 'Stopped playback and cleared the queue.');
	}

	viewQueue() {
		if (this.queue.length === 0) {
			this.#setResponse(1, 'The queue is empty.');
			return;
		}

		const queueString = this.queue
			.map((song, index) => `${index + 1}. **${song.title}**`)
			.join('\n');
		this.#setResponse(1, `**Current Queue:**\n${queueString}`);
	}
}

// export the module
module.exports = Voice;