const $ = jQuery;

$(() => {
	const intervals = {};
	$.each(kna.attrs, (attr, value) => {
		value.attr = attr;
		$(`.kna-detail.${attr}`).data('attr', value);
		setTimeout(() => {
			intervals[attr] = setInterval(loadNext, random(10000, 20000), value);
		}, random(10000));
	});

	$('.kna-btn.pause').on('click', function() {
		const now = $('.hidden', this);
		now.siblings('svg').addClass('hidden');
		now.removeClass('hidden');
	});

	$('.kna-container').closest('.et_pb_section').css('overflow', 'hidden');

	$('.kna-skip').on('click', function() {
		loadNext($(this).closest('.kna-detail').data('attr'), true, $(this).hasClass('next'));
	});
});

const loadNext = (value, force, next) => {
	const attr = value.attr;
	if (!force && $(`.kna-btn.${attr} .svg-pause.hidden`).length) return;

	const me = $(`.kna-detail.${attr}`),
		num = parseInt($('.kna-count num', me).html()) - 1,
		max = value.items.length - 1;
	let i = num;

	if (!force) {
		do {
			i = random(0, value.items.length - 1);
		} while (i == num);
	} else if (next) {
		i++;
		i = i > max ? 0 : i;
	} else {
		i--;
		i = i < 0 ? max : i;
	}

	const item = value.items[i],
		img = $(`.kna-img.${attr}`);
	img.animate({
		opacity: 0,
		left: "-=500"
	}, 300, () => {
		img.attr('src', item.url);
		img.css({
			left: 'initial',
			right: '-500px'
		});
		img.animate({
			opacity: 1,
			right: 0,
			left: 0
		});

		$('.kna-count num', me).startSpinwriter({
			text: i + 1
		});
		$('.kna-attr.trait .kna-val', me).startSpinwriter({
			text: item.trait
		});
		$('.kna-attr.occur .kna-val', me).startSpinwriter({
			text: item.occurrence
		});
		$('.kna-attr.rarity .kna-val', me).startSpinwriter({
			text: item.rarity + '%'
		});
	});

}

const random = (min = 0, max = 100) => Math.floor(Math.random() * (max - min)) + min;

$.fn.startSpinwriter = function(args, callback) {
	if (args.css) {
		this.css(args.css);
	}
	let text = args.text + '';
	text = text.split('');
	const interval = args.interval ? args.interval : 10,
		mode = args.mode ? args.mode : 0;
	var rt = 404;
	write_text(this, text, interval, mode, callback);
	return this;
}


const write_text = function(ele, text, interval, mode, callback) {
	if (mode == 0) {
		return write_next_char(ele, text, 1, interval, callback);
	} else if (mode == 1) {
		return write_wholestring(ele, text, interval, callback);
	}
}

const write_next_char = function(ele, text, index, interval, callback) {
	if (index < text.length + 1) {
		load_current_character(ele, text.slice(0, index).join(''), 0, text, index, interval, callback);
	} else {
		if (callback) {
			callback();
		}
	}
}

const load_current_character = function(ele, str, posi, text, index, interval, callback) {
	if (str.length > 0) {
		var alphabet = " abcdefghijklmnopqrstuvwxyz".split("");
		var number = "0123456789".split("")
		var pt = /[a-zA-Z]/;
		var ptn = /[0-9]/;
		var main_str = str.substr(0, str.length - 1);
		var running_character = str[str.length - 1];
		if ((!pt.test(running_character) || alphabet[posi].toLowerCase() == running_character.toLowerCase()) && (!ptn.test(running_character) || number[posi] === running_character)) {
			ele.html(str);
			index = index + 1;
			write_next_char(ele, text, index, interval, callback);
		} else {
			if (isNaN(parseInt(running_character))) {
				ele.html(main_str + alphabet[posi]);
			} else {
				ele.html(main_str + number[posi]);
			}
			setTimeout(function() {
				posi = posi + 1;
				load_current_character(ele, str, posi, text, index, interval, callback);
			}, interval);
		}
	}
}

function write_wholestring(ele, text, interval, callback) {
	var buffer_word = getBufferedWord(text);
	load_new_string(ele, text, buffer_word, interval, callback);
}

function load_new_string(ele, text, buffer_word, interval, callback) {
	ele.html(buffer_word.join(''))
	if (text.join('') !== buffer_word.join('')) {
		setTimeout(function() {
			buffer_word = getNewBufferedWord(text, buffer_word);
			load_new_string(ele, text, buffer_word, interval, callback)
		}, interval);
	} else {
		if (callback) {
			callback()
		}
	}
}

function getNewBufferedWord(text, buffer_word) {
	var alphabet = " abcdefghijklmnopqrstuvwxyz".split("");
	var number = "0123456789".split("");
	var pt = /[a-zA-Z]/;
	var ptn = /[0-9]/;
	for (var i = 0; i < buffer_word.length; i++) {
		if (buffer_word[i] != text[i]) {
			if (pt.test(buffer_word[i])) {
				if (text[i] == text[i].toUpperCase()) {
					buffer_word[i] = alphabet[alphabet.indexOf(buffer_word[i].toLowerCase()) + 1].toUpperCase();
				} else {
					buffer_word[i] = alphabet[alphabet.indexOf(buffer_word[i]) + 1];
				}
			} else {
				buffer_word[i] = number[number.indexOf(buffer_word[i]) + 1];
			}
		}
	}
	return buffer_word;
}

function getBufferedWord(text_array) {
	var pt = /[a-zA-Z]/;
	var ptn = /[0-9]/;
	var buffered_array = Array(text_array.length);
	for (var i = 0; i < text_array.length; i++) {
		if (!pt.test(text_array[i]) && !ptn.test(text_array[i])) {
			buffered_array[i] = text_array[i];
		} else {
			if (isNaN(text_array[i])) {
				buffered_array[i] = text_array[i] == text_array[i].toUpperCase() ? "A" : "a";
			} else {
				buffered_array[i] = 0;
			}
		}
	}
	return buffered_array;
}