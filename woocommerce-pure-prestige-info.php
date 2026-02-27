<?php
/**
 * Plugin Name: WooCommerce Pure Prestige Info
 * Description: Wyświetla w koszyku ofertę darmowej zawieszki zapachowej z 15-minutowym timerem.
 * Version: 1.0.0
 * Author: goweb.pl
 * Author URI: https://goweb.pl
 * Text Domain: woocommerce-pure-prestige-info
 */

if (!defined('ABSPATH')) {
	exit;
}

final class WooCommerce_Pure_Prestige_Info {
	private const VERSION = '1.0.0';
	private const TIMER_DURATION = 900;
	private const SESSION_TIMER_STARTED = 'wppi_timer_started_at';
	private const SESSION_TIMER_EXPIRED = 'wppi_timer_expired';

	public function __construct() {
		add_action('plugins_loaded', array($this, 'register_hooks'));
	}

	public function register_hooks(): void {
		if (!class_exists('WooCommerce')) {
			return;
		}

		add_action('woocommerce_add_to_cart', array($this, 'maybe_start_timer'), 10, 0);
		add_action('woocommerce_cart_emptied', array($this, 'reset_timer'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('woocommerce_proceed_to_checkout', array($this, 'render_box'));
		add_action('woocommerce_before_mini_cart', array($this, 'render_box'), 15);
	}

	public function maybe_start_timer(): void {
		$session = $this->get_session();

		if (!$session || !$this->cart_has_items()) {
			return;
		}

		if ($session->get(self::SESSION_TIMER_EXPIRED)) {
			return;
		}

		if (!$session->get(self::SESSION_TIMER_STARTED)) {
			$session->set(self::SESSION_TIMER_STARTED, time());
		}
	}

	public function reset_timer(): void {
		$session = $this->get_session();

		if (!$session) {
			return;
		}

		$session->__unset(self::SESSION_TIMER_STARTED);
		$session->__unset(self::SESSION_TIMER_EXPIRED);
	}

	public function enqueue_assets(): void {
		if (is_admin()) {
			return;
		}

		wp_enqueue_style(
			'wppi-styles',
			plugins_url('assets/css/wppi-styles.css', __FILE__),
			array(),
			self::VERSION
		);

		wp_enqueue_script(
			'wppi-script',
			plugins_url('assets/js/wppi-timer.js', __FILE__),
			array(),
			self::VERSION,
			true
		);
	}

	public function render_box(): void {
		$remaining_seconds = $this->get_remaining_seconds();
		$started_at = $this->get_timer_started_at();

		if (!$remaining_seconds || $remaining_seconds <= 0 || $started_at <= 0) {
			return;
		}

		$image_url = plugins_url('assets/img/pure_prestige_cross_sell.png', __FILE__);
		?>
		<div class="wppi-box" data-wppi-started-at="<?php echo esc_attr($started_at); ?>" data-wppi-duration="<?php echo esc_attr(self::TIMER_DURATION); ?>">
			<div class="wppi-box__media">
				<img src="<?php echo esc_url($image_url); ?>" alt="<?php esc_attr_e('Darmowa zawieszka zapachowa', 'woocommerce-pure-prestige-info'); ?>" loading="lazy" />
			</div>
			<div class="wppi-box__content">
				<h3 class="wppi-box__title"><?php esc_html_e('Darmowa zawieszka zapachowa', 'woocommerce-pure-prestige-info'); ?></h3>
				<p class="wppi-box__description"><?php esc_html_e('Dokonaj zakupu w 15 minut i odbierz zawieszkę zapachową w prezencie.', 'woocommerce-pure-prestige-info'); ?></p>
					<div class="wppi-box__timer-row">
						<div class="wppi-box__progress" aria-hidden="true">
							<span class="wppi-box__progress-bar" data-wppi-progress></span>
						</div>
						<strong class="wppi-box__timer" data-wppi-time aria-live="polite"></strong>
					</div>
				</div>
			</div>
			<?php
		}

	private function get_remaining_seconds(): ?int {
		if (!$this->cart_has_items()) {
			return null;
		}

		$session = $this->get_session();

		if (!$session || $session->get(self::SESSION_TIMER_EXPIRED)) {
			return 0;
		}

		$started_at = $this->get_timer_started_at();

		if ($started_at <= 0) {
			return null;
		}

		$elapsed = time() - $started_at;
		$remaining = self::TIMER_DURATION - $elapsed;

		if ($remaining <= 0) {
			$session->set(self::SESSION_TIMER_EXPIRED, true);
			$session->__unset(self::SESSION_TIMER_STARTED);
			return 0;
		}

		return $remaining;
	}

	private function get_timer_started_at(): int {
		$session = $this->get_session();

		if (!$session) {
			return 0;
		}

		$started_at = (int) $session->get(self::SESSION_TIMER_STARTED);

		if ($started_at <= 0) {
			return 0;
		}

		$now = time();
		$offset = (int) (current_time('timestamp') - current_time('timestamp', true));

		// Normalize legacy value that may have been saved with timezone offset.
		if ($offset !== 0) {
			$normalized_started_at = $started_at - $offset;
			$current_delta = abs($started_at - $now);
			$normalized_delta = abs($normalized_started_at - $now);

			if ($normalized_delta < $current_delta) {
				$started_at = $normalized_started_at;
				$session->set(self::SESSION_TIMER_STARTED, $started_at);
			}
		}

		return $started_at;
	}

	private function get_session() {
		if (!function_exists('WC') || !WC()->session) {
			return null;
		}

		return WC()->session;
	}

	private function cart_has_items(): bool {
		return function_exists('WC') && WC()->cart && !WC()->cart->is_empty();
	}
}

new WooCommerce_Pure_Prestige_Info();
