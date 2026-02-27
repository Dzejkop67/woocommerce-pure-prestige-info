(function () {
	'use strict';

	var initializedAttr = 'data-wppi-initialized';
	var fadeOutClass = 'wppi-box--fading-out';

	var formatTime = function (seconds) {
		var minutes = Math.floor(seconds / 60);
		var restSeconds = seconds % 60;
		return String(minutes).padStart(2, '0') + ':' + String(restSeconds).padStart(2, '0');
	};

	var removeWithFade = function (box) {
		if (!box || box.classList.contains(fadeOutClass)) {
			return;
		}

		box.classList.add(fadeOutClass);

		window.setTimeout(function () {
			if (box.parentNode) {
				box.parentNode.removeChild(box);
			}
		}, 280);
	};

	var startCountdown = function (box) {
		if (!box || box.getAttribute(initializedAttr) === '1') {
			return;
		}

		var timerElement = box.querySelector('[data-wppi-time]');
		var progressElement = box.querySelector('[data-wppi-progress]');
		var startedAt = parseInt(box.getAttribute('data-wppi-started-at'), 10);
		var duration = parseInt(box.getAttribute('data-wppi-duration'), 10);
		var endTimestamp = startedAt + duration;

		if (!timerElement || Number.isNaN(startedAt) || Number.isNaN(duration) || duration <= 0) {
			removeWithFade(box);
			return;
		}

		box.setAttribute(initializedAttr, '1');

		var updateTime = function () {
			var now = Math.floor(Date.now() / 1000);
			var remainingSeconds = endTimestamp - now;

			if (remainingSeconds <= 0) {
				return false;
			}

			timerElement.textContent = formatTime(remainingSeconds);

			if (progressElement) {
				var progressPercent = Math.max(0, Math.min(100, (remainingSeconds / duration) * 100));
				progressElement.style.width = progressPercent + '%';
			}

			return true;
		};

		if (!updateTime()) {
			removeWithFade(box);
			return;
		}

		var intervalId = window.setInterval(function () {
			if (!document.body.contains(box)) {
				window.clearInterval(intervalId);
				return;
			}

			if (!updateTime()) {
				window.clearInterval(intervalId);
				removeWithFade(box);
				return;
			}
		}, 1000);
	};

	var initCountdowns = function (root) {
		var context = root || document;
		var countdownBoxes = context.querySelectorAll('[data-wppi-started-at][data-wppi-duration]');

		if (!countdownBoxes.length) {
			return;
		}

		countdownBoxes.forEach(startCountdown);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initCountdowns(document);
		});
	} else {
		initCountdowns(document);
	}

	if (window.jQuery && window.jQuery(document.body).length) {
		window.jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart', function () {
			initCountdowns(document);
		});
	}
})();
