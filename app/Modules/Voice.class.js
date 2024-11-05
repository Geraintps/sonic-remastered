const Command = require("./Command.class");
const { joinVoiceChannel, createAudioPlayer, createAudioResource, AudioPlayerStatus, StreamType } = require('@discordjs/voice');
const ytdl = require('ytdl-core');

class Voice extends Command {
	constructor(postLink, settings = {}, client, command) {
		super(postLink, settings, client, command);
		this.queue = [];
		this.connection = null;
		this.player = createAudioPlayer();
		this.channel = null;
		this.isPlaying = false;
		this.action;
		this.options;
		this.response = {};

		// event listener for when the current track finishes playing
		this.player.on(AudioPlayerStatus.Idle, () => {
			this.isPlaying = false;
			this.queue.shift();
			if (this.queue.length > 0) {
				this.playNext();
			} else {
				this.disconnect();
			}
		});

		// event listener for errors
		this.player.on('error', error => {
			this.setError(error.message);
			this.isPlaying = false;
			this.queue.shift();
			if (this.queue.length > 0) {
				this.playNext();
			} else {
				this.disconnect();
			}
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

	async run() {

		// check we have an action
		if(this.action != '') {
			try {
				switch(this.action) {
					case 'play':
						await this.addToQueue(this.settings.videoUrl, this.settings.user);
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

	async addToQueue(videoUrl, user) {

		// validate YouTube URL
		if (!ytdl.validateURL(videoUrl)) {
			this.setError('Invalid YouTube URL.');
			this.#setResponse(0, `Invalid YouTube URL`);
			return;
		}

		// get the voice channel of the user
		const voiceChannel = user.voice.channel;
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

		// fetch song info
		const songInfo = await ytdl.getInfo(videoUrl);
		const song = {
			title: songInfo.videoDetails.title,
			url: songInfo.videoDetails.video_url,
		};

		// add the song to the queue
		this.queue.push(song);
		this.#setResponse(1, `✅ **${song.title}** has been added to the queue!`);

		// start playing if not already
		if (!this.isPlaying) {
			this.playNext();
		}
	}

	playNext() {
		if (this.queue.length === 0) {
			this.#setResponse(1, 'The queue is empty.');
			return;
		}

		const song = this.queue[0];
		this.isPlaying = true;

		const stream = ytdl(song.url, {
			filter: 'audioonly',
			highWaterMark: 1 << 25, // Increase buffer size
		});

		const resource = createAudioResource(stream, {
			inputType: StreamType.Arbitrary,
		});

		this.player.play(resource);
		this.connection.subscribe(this.player);

		this.#setResponse(1, `🎵 Now playing: **${song.title}**`);
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
		});

		this.channel = voiceChannel;
		this.#setResponse(1, `Joined **${voiceChannel.name}** and ready to play music!`);
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
			this.#setResponse(1, '⏭️ Skipped the current song.');
		} else {
			this.player.stop();
			this.#setResponse(1, '⏭️ Skipped the current song. The queue is now empty.');
		}
	}

	stop() {
		this.queue = [];
		this.player.stop();
		this.disconnect();
		this.#setResponse(1, '⏹️ Stopped playback and cleared the queue.');
	}

	viewQueue() {
		if (this.queue.length === 0) {
			this.#setResponse(1, 'The queue is empty.');
			return;
		}

		const queueString = this.queue
			.map((song, index) => `${index + 1}. **${song.title}**`)
			.join('\n');
		this.#setResponse(1, `🎶 **Current Queue:**\n${queueString}`);
	}
}

// export the module
module.exports = Voice;