# Recipes

These are the changes most plugins built from this template make, each as the files to add or change. A recipe that stores data lists its keys for `Plugin::FOOTPRINT`, which no check enforces beyond the option constants `FootprintTest` covers. Options, transients and user meta under the plugin's `dws_tpl_` prefix are in the footprint already; meta on posts, terms, comments or orders needs its own `meta_prefixes` entry per object type.

- [A safeguard](#a-safeguard)
- [A one-time notice](#a-one-time-notice)
- [A setting](#a-setting)
- [Where stored data lives](#where-stored-data-lives)
- [Renaming a stored setting](#renaming-a-stored-setting)
- [A WordPress settings page](#a-wordpress-settings-page)
- [A product data tab](#a-product-data-tab)
- [A form on an edit screen](#a-form-on-an-edit-screen)
- [A section on WooCommerce's Payments tab](#a-section-on-woocommerces-payments-tab)
- [Recurring jobs](#recurring-jobs)
- [Rewrite rules after activation](#rewrite-rules-after-activation)
- [A script module](#a-script-module)
- [A decision another plugin asks about](#a-decision-another-plugin-asks-about)
- [A filter at a decision point](#a-filter-at-a-decision-point)
- [An action after a change](#an-action-after-a-change)
- [An extensible concept](#an-extensible-concept)
- [Renaming a public hook](#renaming-a-public-hook)
- [Retiring an @api function or constant](#retiring-an-api-function-or-constant)
- [Distribution through wordpress.org](#distribution-through-wordpressorg)
- [Without WooCommerce](#without-woocommerce)

## A safeguard

A safeguard is a restriction that holds even when the rest of the plugin fails to load, such as a capability that stays locked unless the plugin unlocks it. It lives in `Safeguards.php` at the plugin's root, which the entry file requires by path before the floor gate and the autoloader, so it calls only WordPress, what its class defines, and WooCommerce where the plugin requires it. Each safeguard is a static closure, which no code can remove by name, so the plugin relaxes it only through the safeguard's own filter, with an explicit `false`, and only after `dws_plugin_template/boot/succeeded`, which fires once the plugin has booted, so a half-loaded build never relaxes it.

Replace `Safeguards.php` with the class below, its helper and the safeguard. The file runs on any runtime that parses the plugin, so it must parse on PHP 7.4 and call only what its hook hands it, WordPress APIs present at the oldest WordPress the bootstrap package runs on and WooCommerce APIs present since 3.6, guarding anything newer with `function_exists()` or `method_exists()`. WordPress loads the plugin while WooCommerce is inactive, so a safeguard that calls WooCommerce is hooked only once WooCommerce has loaded, as shown after the file. `phpstan.safeguards.neon` checks the WordPress floor, and review the WooCommerce one:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

\defined( 'ABSPATH' ) || exit;

/**
 * Holds the plugin's safeguards, the restrictions that hold even when the rest of the plugin fails to load.
 *
 * The entry requires it by path before the floor gate, so it parses on PHP 7.4 and calls only what the oldest WordPress the bootstrap package runs on provides.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Safeguards {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {}

	// endregion

	// region METHODS

	/**
	 * Hooks the safeguards, as static closures, so that only their own filters relax them.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public static function register(): void {
		add_filter(
			'map_meta_cap',
			static function ( array $caps, string $cap ): array {
				if ( 'dws_tpl_export_data' !== $cap ) {
					return $caps;
				}

				/**
				 * Filters whether the data export stays locked.
				 *
				 * Only an explicit false, once the plugin has booted, unlocks it, so a half-loaded build never relaxes it.
				 *
				 * @since   1.0.0
				 * @version 1.0.0
				 *
				 * @param   bool $restricted Whether the data export stays locked.
				 */
				return self::is_relaxed( apply_filters( 'dws_plugin_template/data_export/should_restrict', true ) ) ? array( 'manage_options' ) : array( 'do_not_allow' );
			},
			10,
			2
		);
	}

	// endregion

	// region HELPERS

	/**
	 * Returns whether a safeguard's filtered value relaxes it, which takes an explicit false once the plugin has finished booting.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   mixed $filtered The value the safeguard's filter returned for true.
	 *
	 * @return  bool
	 */
	protected static function is_relaxed( $filtered ): bool {
		return 0 < did_action( 'dws_plugin_template/boot/succeeded' ) && false === $filtered;
	}

	// endregion
}
```

Every further safeguard is another static closure in `register()` that calls `self::is_relaxed()` around its own literal `apply_filters()`, so each filter keeps its hook docblock and the helper's rule has one home. A value other classes read, such as a lock decision, is a public static of the class, and everything else stays protected.

A safeguard that calls WooCommerce is hooked from one closure, which `register()` runs once WooCommerce has loaded. WooCommerce fires `woocommerce_loaded` once every plugin has loaded, and never while it is inactive, but the request that activates the plugin includes the file after that, so `register()` checks first. A request that activates WooCommerce too never fires it, so these safeguards hold from the next request. Add the closure to `register()`, and hook each such safeguard in its body:

```php
$add_woocommerce_safeguards = static function (): void {
};
if ( 0 < did_action( 'woocommerce_loaded' ) ) {
	$add_woocommerce_safeguards();
} else {
	add_action( 'woocommerce_loaded', $add_woocommerce_safeguards );
}
```

A component relaxes the lock only around the operation that needs it, and removes the relaxation even when the operation throws:

```php
add_filter( 'dws_plugin_template/data_export/should_restrict', '__return_false' );
try {
	$this->export(); // Checks current_user_can( 'dws_tpl_export_data' ).
} finally {
	remove_filter( 'dws_plugin_template/data_export/should_restrict', '__return_false' );
}
```

Add the safeguard's failure test as `tests/Integration/SafeguardsTest.php`. The plugin has booted when the Integration suite runs, so the last row takes `dws_plugin_template/boot/succeeded` back for its duration:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SafeguardsTest extends TestCase {
	protected const string FILTER = 'dws_plugin_template/data_export/should_restrict';

	public static function relaxations(): array {
		return array(
			'none'         => array( array(), false ),
			'null'         => array( array( 'relaxation' => null ), false ),
			'zero'         => array( array( 'relaxation' => 0 ), false ),
			'empty-string' => array( array( 'relaxation' => '' ), false ),
			'false'        => array( array( 'relaxation' => false ), true ),
		);
	}

	#[DataProvider( 'relaxations' )]
	public function test_only_an_explicit_false_relaxes_the_export_lock( array $spec, bool $relaxed ): void {
		$relaxation = static fn (): mixed => $spec['relaxation'];
		if ( \array_key_exists( 'relaxation', $spec ) ) {
			add_filter( self::FILTER, $relaxation );
		}

		try {
			self::assertSame( $relaxed ? array( 'manage_options' ) : array( 'do_not_allow' ), map_meta_cap( 'dws_tpl_export_data', 0 ) );
		} finally {
			remove_filter( self::FILTER, $relaxation );
		}
	}

	public function test_an_explicit_false_does_not_relax_the_export_lock_before_the_plugin_booted(): void {
		global $wp_actions;
		self::assertGreaterThan( 0, did_action( 'dws_plugin_template/boot/succeeded' ) );
		$fired = $wp_actions['dws_plugin_template/boot/succeeded'];
		unset( $wp_actions['dws_plugin_template/boot/succeeded'] );
		add_filter( self::FILTER, '__return_false' );

		try {
			self::assertSame( array( 'do_not_allow' ), map_meta_cap( 'dws_tpl_export_data', 0 ) );
		} finally {
			remove_filter( self::FILTER, '__return_false' );
			$wp_actions['dws_plugin_template/boot/succeeded'] = $fired;
		}
	}
}
```

## A one-time notice

A one-time notice shows once, on the user's next admin screen, such as after a form post redirects. The plugin's `AdminNotices` is in `Plugin::COMPONENTS` and in the container, so a component takes it in its constructor:

```php
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\AdminNotices;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\Notice;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\NoticeType;

// ...

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   AdminNotices $notices The plugin's admin notices.
	 */
	public function __construct(
		protected AdminNotices $notices
	) {}

	// ...

	/**
	 * Tells the current user, on their next admin screen, how many rows the export wrote.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   int $rows The number of rows the export wrote.
	 *
	 * @return  void
	 */
	protected function report_export( int $rows ): void {
		$this->notices->add(
			new Notice(
				'export-' . wp_generate_uuid4(),
				/* translators: %d: The number of rows. */
				esc_html( \sprintf( _n( 'The export wrote %d row.', 'The export wrote %d rows.', $rows, 'dws-plugin-template' ), $rows ) ),
				NoticeType::Success
			)
		);
	}
```

The notice's scope defaults to the current user and its message is HTML, so escape it. A notice for every administrator of the site takes `NoticeScope::Site`, a capability and `per_viewer: true`, since without it the first administrator's viewing consumes it for everyone; one that stays until dismissed takes `persistent: true`.

## A setting

A setting holds one value per site, which only a settings screen saves. Whatever the number of settings, each is one autoloaded option, declared once on `Options`, shown by a field and stored by the migrations.

1. In `src/Options.php`, add the key beside its feature's other keys, with a docblock, and its default to `DEFAULTS` in the form the option stores it: `'yes'` or `'no'` for a checkbox, digits for a number, a list for a multiselect, and `''` for text whose default is translated, which the component that shows the setting supplies after `init`, as the greeting's message does:

```php
	/**
	 * The option that holds the number of days after a customer's last paid order during which the greeting shows.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @var     string
	 */
	public const string GREETING_DAYS = 'dws_tpl_greeting_days';

	// ...

	public const array DEFAULTS = array(
		// ...
		self::GREETING_DAYS    => '30',
	);
```

2. Add the field to its feature's fields class, here `src/Greeting/GreetingSettings.php`. Its `default` reads `DEFAULTS`, and a number field states its bounds in `custom_attributes`, with a `min` beside any `step`, since the browser and the save both count the steps from it:

```php
			array(
				'type'              => 'number',
				'id'                => Options::GREETING_DAYS,
				'title'             => __( 'Days since the last order', 'dws-plugin-template' ),
				'desc'              => __( 'Customers see the greeting for this many days after their last paid order.', 'dws-plugin-template' ),
				'default'           => Options::DEFAULTS[ Options::GREETING_DAYS ],
				'placeholder'       => Options::DEFAULTS[ Options::GREETING_DAYS ],
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
```

- The framework holds a number field to its bounds and stores an emptied one as empty, which the reader takes as the default the placeholder shows.
- A field that needs a rule the framework does not apply takes a `validate` callable, a protected static of the fields class handed over as `self::validate_…( ... )`.
- `Settings` keeps the WooCommerce tab's plugin-level fields, such as the consent to delete data, and spreads in the fields each feature's fields class returns. A fields class is final, with a protected constructor and a public static `get_settings_fields()`, as `GreetingSettings` is.
- A feature gated on another plugin spreads its fields only while that plugin is active; its keys and defaults stay on `Options`, so its settings are still stored and uninstalled.

3. Read the setting through `Options`' typed readers, such as `Options::get_int( Options::GREETING_DAYS )`, which return the default while nothing valid is stored.

4. In the release that adds the setting, append `self::add_settings( ... )` to `Migrations::get_steps()`, after the release's rename steps, so that existing sites store the new default. The Unit `MigrationsTest` then names the fingerprint to append to its `SEEDED_KEY_SETS`. Until the plugin's first release, the first step stores the default already, and the new fingerprint replaces the last one.

## Where stored data lives

Each value the plugin stores is one of these kinds, the first that fits, and each kind has one home:

- **A setting** holds one value per site, which the plugin declares and only a settings save writes: one autoloaded option, its key on `Options` and its default in `DEFAULTS`, as [A setting](#a-setting) adds. A setting never stores code or the name of a function to run, which would let anyone who saves the settings make the plugin run any function; a choice that leaves a decision to code is decided by a filter, as [A filter at a decision point](#a-filter-at-a-decision-point) shows.
- **A per-object override** is a setting's value for one product, order, user or term: one meta key, a constant on the class that stores it, as the product data tab and edit-screen recipes show.
- **A list setting** is a list edited on the settings screen whose entries pass none of the entity tests below: one option holding the list, as the Payments-tab recipe's multiselect stores its chosen keys.
- **An entity** is a record whose id another record stores, one written outside a settings save, or one that needs its own capability or REST route: a post type with meta, or a table of its own, which the footprint's `post_types`, `meta` and `tables` kinds remove.
- **Runtime state** is a value the plugin's code writes outside a form save, such as a sequence counter: an option of its own, written through core's `OptionRows`, whose conditional writes keep two requests from losing an update, never autoloaded and never in `DEFAULTS`. Its key is a constant on the class that owns it, under the plugin's prefix, and `FootprintTest`, which reads only `Options`' keys, gains a row for it:

```php
	public function test_it_covers_the_request_counter(): void {
		self::assertTrue( \array_any( Plugin::FOOTPRINT['option_prefixes'], static fn ( string $prefix ): bool => \str_starts_with( RequestNumbers::COUNTER, $prefix ) ) );
	}
```

- **A marker that only spares repeated work for a while** is a transient with an expiry, its name a constant on the class that sets it, which the footprint's `_transient_` prefixes cover, as the recurring-jobs recipe's repair gate is.

No setting is written through `OptionRows`, whose writes take a row out of autoload.

## Renaming a stored setting

A stored option's name is part of the plugin's contract, since other plugins read it with `get_option()`, so a rename ships in a major release:

1. Change the key's value in `src/Options.php`; its readers, its field and `DEFAULTS` follow.
2. Add a plain step to `src/Migrations.php` that copies the old row over the new one and deletes the old row. The old row wins, so a value the site stored outlives a default that a seeding step, or a settings save, stored under the new name before the step ran. Append it after the steps earlier releases shipped, which keep their places, and before the release's own batched steps and its `self::add_settings( ... )`, which the changed keys call for, as [A setting](#a-setting) says:

```php
	/**
	 * Moves the stored record limit from its 1.x name to its 2.0 name, letting the old row win.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @throws  \RuntimeException Thrown when the limit could not be moved.
	 *
	 * @return  void
	 */
	protected static function rename_record_limit(): void {
		$stored = get_option( 'dws_tpl_max_records' );
		if ( false === $stored ) {
			return;
		}

		if ( get_option( 'dws_tpl_record_limit' ) !== $stored && ! update_option( 'dws_tpl_record_limit', $stored, true ) ) {
			throw new \RuntimeException( 'The option dws_tpl_record_limit could not be stored.' );
		}
		if ( ! delete_option( 'dws_tpl_max_records' ) ) {
			throw new \RuntimeException( 'The option dws_tpl_max_records could not be deleted.' );
		}
	}
```

3. Keep the name of a setting that hides or restricts something, whose default is less restrictive than a value a site may have stored. Gates read settings on `plugins_loaded`, before the migrations run on `wp_loaded`, so on the first request after the update the new name would read its default and show what the site chose to hide.
4. Add a changelog fragment with `composer changelog:add` that names the old and the new option, with a major significance.

## A WordPress settings page

An options page adds a screen under a WordPress admin menu and stores each field in its own option through the Settings API, as WooCommerce's settings tabs do, so a setting stores and reads alike on either screen. WooCommerce settings belong on a WooCommerce tab, as `src/Settings.php` does.

Add the setting to `src/Options.php` as [A setting](#a-setting) does. The non-WooCommerce recipe below uses the same names:

```php
	/**
	 * The option that holds the most records the plugin keeps.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string LIMIT = 'dws_tpl_limit';

	// ...

	public const array DEFAULTS = array(
		// ...
		self::LIMIT => '1',
	);
```

Add the page as a component, `src/SettingsPage.php`, and list it in `Plugin::COMPONENTS`. The page takes the plugin's identity, which the container provides. Its definition names the page and its tabs and gives each tab's fields apart, so the page asks only for the fields of the tab it shows or saves:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginIdentity;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\AdminPage\OptionsPage;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\AdminPage\OptionsPageDefinitionInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds the plugin's settings page under Settings.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class SettingsPage implements ComponentInterface, OptionsPageDefinitionInterface {
	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   PluginIdentity $identity The plugin's identity, which the page takes.
	 */
	public function __construct(
		protected PluginIdentity $identity
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		new OptionsPage( $this->identity, Plugin::PREFIX, $this )->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_options_page( string $page_id ): array {
		return array(
			'parent'     => 'options-general.php',
			'capability' => 'manage_options',
			'page_title' => __( 'DWS Plugin Template', 'dws-plugin-template' ),
			'menu_title' => __( 'DWS Plugin Template', 'dws-plugin-template' ),
			'tabs'       => array(
				array(
					'slug'  => 'general',
					'title' => __( 'General', 'dws-plugin-template' ),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_tab_fields( string $page_id, string $tab_slug ): array {
		return match ( $tab_slug ) {
			'general' => array(
				array(
					'type'              => 'number',
					'id'                => Options::LIMIT,
					'title'             => __( 'Limit', 'dws-plugin-template' ),
					'default'           => Options::DEFAULTS[ Options::LIMIT ],
					'placeholder'       => Options::DEFAULTS[ Options::LIMIT ],
					'custom_attributes' => array(
						'min'  => '1',
						'step' => '1',
					),
				),
			),
			default   => array(),
		};
	}

	// endregion
}
```

- Each tab saves as the settings group named by the page's id and the tab's slug, here `dws_tpl_general`.
- A field's id is the option it stores, which starts with `dws_tpl_`, since the page's capability writes it; the page leaves out any other field and reports it through `_doing_it_wrong()`.
- The framework holds the limit to its `min` and `step` and stores it as plain digits. A rejected value keeps the stored one, and its message shows among the page's notices.
- Another plugin adds tabs through `dws_plugin_template/options_page/tabs`, and fields through `dws_plugin_template/options_page/fields`, which passes each tab's slug; a tab another plugin adds gets none of the definition's fields, since `get_tab_fields()` returns none for a slug it does not name.

Read the limit with `Options::get_int( Options::LIMIT )`, which returns the default until a valid limit is stored.

## A product data tab

A product data tab adds a panel to the product editor's Product data box, whose fields WooCommerce renders and saves as the product's meta. Add it as a component, `src/ProductData.php`, which takes the plugin's identity, and list it in `Plugin::COMPONENTS`:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginIdentity;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\ProductData\ProductDataTab;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\ProductData\ProductDataTabDefinitionInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds the packing tab to the product editor.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class ProductData implements ComponentInterface, ProductDataTabDefinitionInterface {
	// region FIELDS AND CONSTANTS

	/**
	 * The meta key that holds the box a product ships in.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string BOX = '_dws_tpl_box';

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   PluginIdentity $identity The plugin's identity, which the tab takes.
	 */
	public function __construct(
		protected PluginIdentity $identity
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		new ProductDataTab( $this->identity, Plugin::PREFIX, $this )->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_product_data_tab( string $tab_id, \WC_Product $product ): array {
		return array(
			'label'  => __( 'Packing', 'dws-plugin-template' ),
			'groups' => array(
				array(
					'fields' => array(
						array(
							'type'  => 'text',
							'id'    => self::BOX,
							'label' => __( 'Box', 'dws-plugin-template' ),
						),
					),
				),
			),
		);
	}

	// endregion
}
```

Read the stored value with `ProductMeta::get_value( $product, ProductData::BOX )`. The leading underscore hides the key from WordPress's Custom Fields box. Post meta is not in the footprint until its type is, so declare the prefix for uninstall in `Plugin::FOOTPRINT`, and add `meta_prefixes?: array<string, list<non-empty-string>>` to its `@var` shape:

```php
'meta_prefixes'             => array( 'post' => array( '_' . self::PREFIX . '_' ) ),
```

A field with its own `render` callable returns its control as inline markup, escapes, names and describes it, and prints a hidden empty input before it unless the field is disabled. Without that input, a cleared control posts nothing, and the save leaves the stored value untouched. It also takes a `validate` callable that returns the value to store or a `WP_Error`, since the framework refuses to save a custom control's value that nothing validates.

## A form on an edit screen

An object form adds fields to an edit screen, such as a post's meta box, a user's profile or a term's form, and stores each field on the edited object. It reports a rejected value through the plugin's `AdminNotices`. Add it as a component, `src/PostNote.php`, which takes the plugin's identity and its notices, and list it in `Plugin::COMPONENTS`:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\AdminNotices;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\PluginIdentity;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\EditScreen\Hosts\PostMetaBox;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\EditScreen\ObjectForm;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Settings\EditScreen\ObjectFormDefinitionInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds an editorial note to the post editor.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class PostNote implements ComponentInterface, ObjectFormDefinitionInterface {
	// region FIELDS AND CONSTANTS

	/**
	 * The meta key that holds a post's editorial note.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	protected const string NOTE = '_dws_tpl_note';

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   PluginIdentity $identity The plugin's identity, which the form takes.
	 * @param   AdminNotices   $notices  The plugin's admin notices, which report the form's rejections.
	 */
	public function __construct(
		protected PluginIdentity $identity,
		protected AdminNotices $notices
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		new ObjectForm( $this->identity, Plugin::PREFIX . '_note', $this, new PostMetaBox( array( 'post' ), 'side' ), $this->notices )->register_hooks();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_object_form( string $form_id, int $object_id ): array {
		return array(
			'title'      => __( 'Editorial note', 'dws-plugin-template' ),
			'capability' => 'edit_posts',
			'fields'     => array(
				array(
					'type'  => 'textarea',
					'id'    => self::NOTE,
					'title' => __( 'Note', 'dws-plugin-template' ),
				),
			),
		);
	}

	// endregion
}
```

The host checks that the user can edit the post itself, and the form's `capability` is one more capability the user needs, checked without an object, so it names a primitive capability such as `edit_posts` rather than `edit_post`. The form verifies its own nonce before it asks for the definition on a save. Declare the meta for uninstall in `Plugin::FOOTPRINT`, as the product data tab does. A field with its own `render` callable prints a hidden empty input before its control unless the field is disabled, and takes a `validate` callable, for the reasons that recipe gives.

## A section on WooCommerce's Payments tab

A section on an existing WooCommerce tab, here the Payments tab (`checkout`), holds settings that belong beside WooCommerce's own. WooCommerce renders the Payments tab as its own page and lists no extension sections there, so the section is reached through its direct link, which the plugin's row on the Plugins screen carries. Add it as a component, `src/PaymentSettings.php`, and list it in `Plugin::COMPONENTS`:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SectionFieldsDefinitionInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\WooCommerce\Setting\SettingsSection;

\defined( 'ABSPATH' ) || exit;

/**
 * Adds the plugin's section to WooCommerce's Payments tab, and links to it from the plugin's row.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class PaymentSettings implements ComponentInterface, SectionFieldsDefinitionInterface {
	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_action( 'woocommerce_settings_sections_registration', array( $this, 'register_section' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( DWS_TPL_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function get_section_fields( string $tab_id, string $section_id ): array {
		$methods = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof \WC_Payment_Gateway ) {
				$methods[ $gateway->id ] = $gateway->get_method_title();
			}
		}

		return array(
			array(
				'type'  => 'title',
				'id'    => Plugin::PREFIX . '_payment_options',
				'title' => __( 'DWS Plugin Template', 'dws-plugin-template' ),
			),
			array(
				'type'    => 'checkbox',
				'id'      => Options::PAYMENT_NOTES,
				'title'   => __( 'Payment notes', 'dws-plugin-template' ),
				'desc'    => __( 'Add a note to each order when its payment completes', 'dws-plugin-template' ),
				'default' => Options::DEFAULTS[ Options::PAYMENT_NOTES ],
			),
			array(
				'type'    => 'multiselect',
				'id'      => Options::PAYMENT_METHODS,
				'title'   => __( 'Payment methods', 'dws-plugin-template' ),
				'desc'    => __( 'Add the note only to orders paid with these methods', 'dws-plugin-template' ),
				'class'   => 'wc-enhanced-select',
				'options' => $methods,
				'default' => Options::DEFAULTS[ Options::PAYMENT_METHODS ],
			),
			array(
				'type' => 'sectionend',
				'id'   => Plugin::PREFIX . '_payment_options',
			),
		);
	}

	// endregion

	// region HOOKS

	/**
	 * Registers the plugin's section on the Payments tab.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   SettingsSectionRegistry $registry WooCommerce's registry of settings sections.
	 *
	 * @return  void
	 */
	public function register_section( SettingsSectionRegistry $registry ): void {
		$registry->register(
			new SettingsSection(
				Plugin::PREFIX,
				array(
					'tab'   => 'checkout',
					'label' => __( 'DWS Plugin Template', 'dws-plugin-template' ),
				),
				$this
			)
		);
	}

	/**
	 * Adds the link to the plugin's section to its row on the Plugins screen.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string[] $links The row's action links.
	 *
	 * @return  string[]
	 */
	public function add_settings_link( array $links ): array {
		\array_unshift( $links, \sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . Plugin::PREFIX ) ), esc_html__( 'Settings', 'dws-plugin-template' ) ) );
		return $links;
	}

	// endregion
}
```

WooCommerce stores each field in its own option under its id, flat rather than in a group: a checkbox stores `yes` or `no`, which `Options::get_bool()` reads, and a multiselect the list of the chosen keys, which a list reader reads. Add each key to `src/Options.php` as [A setting](#a-setting) does, with its default in `DEFAULTS`, `'no'` for the checkbox and an empty array for the multiselect, whose `@var` then reads `array<string, string|list<string>>`, and the list reader beside the other typed readers:

```php
	/**
	 * The option that holds 'yes' where the site adds a note to each order when its payment completes.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string PAYMENT_NOTES = 'dws_tpl_payment_notes';

	/**
	 * The option that holds the payment methods whose orders get the payment note.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string PAYMENT_METHODS = 'dws_tpl_payment_methods';

	// ...

	/**
	 * Returns an option as a list of strings, keeping the stored strings in order.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string $key The option's key.
	 *
	 * @return  list<string>
	 */
	public static function get_list( string $key ): array {
		$default = self::get_default( $key );
		$stored  = get_option( $key, $default );
		$stored  = \is_array( $stored ) ? $stored : $default;
		return \is_array( $stored ) ? \array_values( \array_filter( $stored, \is_string( ... ) ) ) : array();
	}
```

WooCommerce's save drops a multiselect key of `'0'` or `''`, so each key stays truthy, as gateway ids are.

## Recurring jobs

A recurring job runs a hook at an interval through the plugin's scheduler, which uses Action Scheduler where it is loaded and WP-Cron otherwise. Either can run an invocation twice or skip one, so the job's callback leaves its data consistent after a duplicate or a missed run.

Add the job as a component, `src/Pruning.php`, and list it in `Plugin::COMPONENTS`. It ensures the schedule on `wp_loaded` at most once an hour, behind a transient that lives an hour, and again when Action Scheduler asks every day. Setting the transient first narrows the window in which two requests both ensure it, and `ensure_recurring()` repairs a duplicate chain on its next pass:

```php
<?php declare( strict_types=1 );

namespace DeepWebSolutions\PluginTemplate;

use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\ComponentInterface;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Scheduling\Recurrence;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Scheduling\ScheduledHook;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Scheduling\SchedulerInterface;
use DeepWebSolutions\PluginTemplate\Scoped\Psr\Log\LoggerInterface as PsrLoggerInterface;

\defined( 'ABSPATH' ) || exit;

/**
 * Prunes the plugin's expired records once a day.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Pruning implements ComponentInterface {
	// region FIELDS AND CONSTANTS

	/**
	 * The hook the daily prune runs on.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string HOOK = 'dws_plugin_template/records/prune';

	/**
	 * The transient that marks the daily prune as ensured within the last hour.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     string
	 */
	public const string ENSURED = 'dws_tpl_prune_ensured';

	// endregion

	// region MAGIC METHODS

	/**
	 * Constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   SchedulerInterface $scheduler The plugin's scheduler.
	 * @param   PsrLoggerInterface $logger    The plugin's logger.
	 */
	public function __construct(
		protected SchedulerInterface $scheduler,
		protected PsrLoggerInterface $logger
	) {}

	// endregion

	// region INHERITED METHODS

	/**
	 * {@inheritDoc}
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	#[\Override]
	public function register_hooks(): void {
		add_action( self::HOOK, array( $this, 'prune' ) );
		add_action( 'wp_loaded', array( $this, 'repair_schedule' ) );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'ensure_schedule' ) );
	}

	// endregion

	// region HOOKS

	/**
	 * Ensures the daily prune unless a request did so within the last hour.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function repair_schedule(): void {
		if ( false !== get_transient( self::ENSURED ) ) {
			return;
		}

		set_transient( self::ENSURED, 1, HOUR_IN_SECONDS );
		$this->ensure_schedule();
	}

	/**
	 * Schedules the daily prune unless it runs at that interval already, logging a scheduler that refuses.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function ensure_schedule(): void {
		$ensured = $this->scheduler->ensure_recurring( new ScheduledHook( self::HOOK ), Recurrence::from_interval( DAY_IN_SECONDS ) );
		if ( true !== $ensured ) {
			$this->logger->warning(
				'Could not schedule {hook}: {error}',
				array(
					'hook'      => self::HOOK,
					'error'     => $ensured->get_error_message(),
					'exception' => $ensured->get_error_data(),
				)
			);
		}
	}

	/**
	 * Deletes the records that have expired.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function prune(): void {
		// Delete by expiry, so a second run in a day finds nothing left and a missed day catches up.
	}

	// endregion
}
```

Then:
- Add `Pruning::HOOK` to `cron_hooks` in `Plugin::FOOTPRINT`. Deactivation unschedules every hook there through the plugin's scheduler, and uninstall clears WP-Cron's copies, while Action Scheduler's are in the footprint's `as_groups` already.
- Delete the repair gate's transient on activation, so the next request ensures the jobs: register an activation closure in `functions.php` beside the deactivation hook, as the rewrite-rules recipe shows, that calls `delete_transient( Pruning::ENSURED )`. The footprint's `_transient_` prefixes already cover the transient. A plugin that also takes the rewrite-rules recipe keeps one activation closure that does both.
- Change an interval in code only: `ensure_recurring()` replaces a schedule whose interval differs on the next pass of the repair gate. A "run now" schedules the same hook with an argument that sets it apart, or calls `prune()` directly.

## Rewrite rules after activation

WordPress builds its rewrite rules from the post types and taxonomies registered on `init`. The request that activates the plugin does not boot it, because WordPress includes the plugin's file after `plugins_loaded`, so flushing there would drop the plugin's rules. Delete the stored rules instead: the next request rebuilds them once the plugin has registered its types.

In `functions.php`, beside the deactivation hook, as a static closure like the plugin's other hooks there:

```php
register_activation_hook(
	DWS_TPL_FILE,
	static function (): void {
		delete_option( 'rewrite_rules' );
	}
);
```

A plugin that also takes the recurring-jobs recipe keeps one activation closure that does both. Add the same `delete_option( 'rewrite_rules' )` to the deactivation closure, so the next request rebuilds the rules without them. Both act on the current site only, as WordPress's own activation does; a network activation leaves the other sites' rules until they next rebuild them.

## A script module

A script module is a JavaScript module WordPress loads through its import map, such as a block's view script for the Interactivity API. wp-scripts builds script modules only from a block's `block.json`, and WordPress registers each one from the block's metadata, with the imports wp-scripts recorded, and enqueues it when the block renders, so the plugin registers nothing itself.

Name the module in the block's `block.json`, here `blocks/src/example-notice/block.json`:

```json
"viewScriptModule": "file:./view.js"
```

Add the module beside it, as `blocks/src/example-notice/view.ts`:

```ts
import { store } from '@wordpress/interactivity';

store( 'dws-plugin-template/example-notice', {} );
```

Declare the module's import, which today arrives only through `@wordpress/block-editor`:

```sh
npm install --save-dev @wordpress/interactivity
```

Build the blocks' modules with their scripts, by adding `--experimental-modules` to `build:blocks` and `start:blocks` in `package.json`:

```json
"build:blocks": "wp-scripts build --webpack-src-dir=blocks/src --output-path=blocks/build --blocks-manifest --experimental-modules",
"start:blocks": "wp-scripts start --webpack-src-dir=blocks/src --output-path=blocks/build --blocks-manifest --experimental-modules"
```

The build writes `view.js` with a `view.asset.php` whose `type` is `module`, and `src/Blocks.php`'s registration from the manifest picks it up.

## A decision another plugin asks about

Other plugins integrate only through the plugin's global functions, its models in `models/`, its hooks and WordPress's and WooCommerce's APIs on the names AGENTS.md's Contract section lists, never through `src/` or `Safeguards.php`. A function, model method or hook enters that surface without a named caller when its signature holds only scalars, WordPress and WooCommerce types and the plugin's models, and when it:

- exposes a noun or verb the plugin's screens give an admin, a decision the plugin makes, an event when it changes its data, or the inverse of an operation it exposes;
- guards what the literal's direct use would skip, such as a lock, a per-site key, a list's read-modify-write or paired keys;
- runs the implementation the plugin's own code runs, an exception taking the owner's ruling.

The surface keeps one model per noun, a new capability of a concept being a method on its model; puts an open set of interchangeable behaviours behind one interface, as [An extensible concept](#an-extensible-concept) shows; gives a setting no reader, exposing the decision it feeds instead; fires an action for each change to the plugin's data, as [An action after a change](#an-action-after-a-change) shows; and admits nothing lock-bound or php-scoper-typed.

A decision is the usual first entry. `includes/greeting.php` declares `dws_tpl_should_show_greeting()`, a one-line delegate to `AccountDashboardGreeting::should_show()`, which the greeting's render also calls:

- Make the decision once, in a public static on the `src/` class that makes it, and have the plugin's own code and the `@api` function both call it, so the two give one answer. Put the function in a file in `includes/` named for its topic.
- Name it for the decision with the hook rules' predicates, `should_` for a decision a listener can change, `is_`, `has_` or `are_` for a state and `can_` for a capability, and type its return.
- Keep the function defined and have the static answer no until the plugin has booted, as `should_show()` does until `dws_plugin_template/boot/succeeded` has fired, so the function never reaches WooCommerce where it is inactive or where the boot failed.
- Expose no reader for a setting the decision reads, nor a generic `dws_tpl_get_option( $key )`, which would make every key, type and default contract. Another plugin that needs a stored value reads it through `get_option()` on the option's name.
- Another plugin calls the function no earlier than `plugins_loaded`, or than the hook its docblock names, such as `wp_loaded` for the greeting's, and behind `function_exists()`, as AGENTS.md's add-on convention asks of every `@api` function, since the plugin may load later or stay active below its floors without defining it.
- Tag the function `@api`, which `GlobalsTest` checks, and give it an Integration row. It is contract from its first release, so a break of it ships as the next major, as AGENTS.md's Contract section says.

## A filter at a decision point

Where other plugins should be able to change what the plugin decides, apply a filter at that decision and pass it the object the decision is for, so a listener changes only the cases it means. `AccountDashboardGreeting::should_show()` decides through a filter once it has counted the customer's paid orders:

```php
		/**
		 * Filters whether a customer sees the account dashboard greeting.
		 *
		 * @since   1.0.0
		 * @version 1.0.0
		 *
		 * @param   bool     $shown    Whether the customer's paid orders reach the set number.
		 * @param   \WP_User $customer The customer.
		 */
		return true === apply_filters( 'dws_plugin_template/greeting/should_show', \is_array( $paid ) && \count( $paid ) >= $threshold, $customer );
```

- Name the filter by AGENTS.md's hook rules, a predicate for a yes-or-no decision, and keep the strict comparison at the call site, so a listener that returns anything but `true` leaves the greeting out.
- Ask the filter only where the plugin acts at all: the greeting answers no until the plugin has booted, while it is switched off or where the id names no user, before the filter, so a listener cannot turn on a greeting the site has switched off.
- A choice that leaves the decision to code, such as "custom", is answered by such a filter, which a site fills with its own code; no setting stores code or the name of a function to run.
- Apply no filter to every read of a setting: it would fire for every setting with no context, while WordPress's `pre_option_{$option}` and `option_{$option}` filters already override one stored value where a site needs that. A plugin whose v1 filtered each settings read keeps that filter, with v1's field ids, through any change to how its settings are stored.
- The filter's name and arguments are contract from the release that adds it, as every hook the plugin fires.

## An action after a change

Each change the plugin makes to its data fires an action, so other plugins react to it without hooking WordPress's lower-level writes, which fire for every plugin's data and before the change is whole. For a record the plugin stores:

```php
		/**
		 * Fires after the plugin stores a customer's record.
		 *
		 * @since   1.1.0
		 * @version 1.1.0
		 *
		 * @param   int $record_id   The record's id.
		 * @param   int $customer_id The customer's user id.
		 */
		do_action( 'dws_plugin_template/record/stored', $record_id, $customer_id );
```

- Name it under the area it concerns, its last level a past participle, as AGENTS.md's hook rules say, with one action per kind of change, such as `record/stored` and `record/deleted`.
- Fire it after the write has succeeded and after any lock the write held is released, so a listener sees the finished change and can write in turn.
- Pass ids, and what a listener cannot read back afterwards, such as the previous value; a listener loads the rest through the plugin's functions and models.
- Fire it from the one place that makes the change, so every path to the change, the plugin's screens and its `@api` functions alike, announces it.
- The action's name and arguments are contract from the release that adds it.

## An extensible concept

Where the plugin decides through an open set of interchangeable behaviours, such as the rules that unlock a payment method, each behaviour is an instance of one global interface, registered and looked up through exactly three `@api` functions, so a new behaviour adds no function. The template ships no such set. For export formats a site chooses from:

1. Declare the interface in `models/`, which Composer's classmap loads, and tag it `@api`:

```php
<?php declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Writes a customer's records in one export format.
 *
 * @api
 *
 * @since   1.1.0
 * @version 1.1.0
 */
interface DWS_TPL_Export_Format {
	/**
	 * Returns the format's id, unique among the registered formats.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @return  string
	 */
	public function get_id(): string;

	/**
	 * Returns the format's name, translated, which callers read from init on.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @return  string
	 */
	public function get_label(): string;

	/**
	 * Returns a customer's records written in the format.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @param   int $customer_id The customer's user id.
	 *
	 * @return  string
	 */
	public function write( int $customer_id ): string;
}
```

A released interface freezes, since a new method breaks every implementer, so a later capability is a sub-interface that extends it.

2. Hold the registered formats for the request in a final class in `src/` named for the set, here `ExportFormats`, whose `get()` returns `self::$formats[ $id ] ?? null` and whose `get_all()` returns them all:

```php
	/**
	 * Registers a format, unless one with its id is registered already.
	 *
	 * @since   1.1.0
	 * @version 1.1.0
	 *
	 * @param   \DWS_TPL_Export_Format $format The format.
	 *
	 * @return  bool
	 */
	public static function register( \DWS_TPL_Export_Format $format ): bool {
		$id = $format->get_id();
		if ( isset( self::$formats[ $id ] ) ) {
			_doing_it_wrong( __METHOD__, esc_html( "The export format '$id' is registered already." ), '1.1.0' );
			return false;
		}

		self::$formats[ $id ] = $format;
		return true;
	}
```

3. In `includes/export-formats.php`, declare `dws_tpl_register_export_format( DWS_TPL_Export_Format $format ): bool`, `dws_tpl_get_export_format( string $id ): ?DWS_TPL_Export_Format` and `dws_tpl_get_export_formats(): array`, each tagged `@api` and a one-line delegate to `ExportFormats`.

4. Register and decide:

- A built-in format registers itself in its component's `register_hooks()`, so a format whose component is gated off never registers and its lookup returns `null`. Another plugin registers its own on `dws_plugin_template/boot/succeeded`, once every component has registered, adding that callback before `plugins_loaded` reaches priority 15, where this plugin boots and fires the action.
- Read the set only when deciding or rendering, never in `register_hooks()`, where later components and other plugins have not registered yet. Nothing seals it, so a late registration takes part in every later decision.
- One callback decides through the set, here the export, which writes in the format the site chose, `ExportFormats::get( $chosen )?->write( $customer_id )`, so a new format needs no callback of its own. Log a `Throwable` from another plugin's format and treat it as no format.
- Never add a function per format: the three functions serve every format the set will hold.

## Renaming a public hook

A public hook's name is part of the plugin's contract, as its arguments, its options and its REST routes are. To rename one in a released plugin:

1. Rename the literal at every `do_action()` or `apply_filters()` and in its docblock, which keeps its `@since` and moves its `@version` to the release.
2. Fire the old name too, until the next major, through `do_action_deprecated()` or `apply_filters_deprecated()` with the release's version and the new name as its replacement. The old name goes first, so a filter's callbacks on the new name see the final value:

```php
$restricted = apply_filters_deprecated( 'dws_plugin_template/export/should_restrict', array( true ), '1.1.0', 'dws_plugin_template/data_export/should_restrict' );

/**
 * Filters whether the data export stays locked.
 *
 * Only an explicit false, once the plugin has booted, unlocks it, so a half-loaded build never relaxes it.
 *
 * @since   1.0.0
 * @version 1.1.0
 *
 * @param   bool $restricted Whether the data export stays locked.
 */
$restricted = apply_filters( 'dws_plugin_template/data_export/should_restrict', $restricted );
```

3. Add a changelog fragment with `composer changelog:add` that names the old and the new hook, with a minor significance and the Deprecated type.
4. Migrate stored data that holds the name, such as a scheduled job's hook, in a migration step that converges from any partial state: unschedule the old name, then ensure the new one.
5. In the next major, delete the deprecated call, with a major fragment that names the old hook as removed.

## Retiring an @api function or constant

An `@api` function or constant that a tagged release shipped is contract until the next major, so it keeps working until then; one that no release has shipped is deleted outright. To retire one in a released plugin:

1. Keep it, delegating to its replacement where it has one, with a `@deprecated` tag that names the release and the replacement. A function raises WordPress's deprecation notice through `_deprecated_function()`, and a constant declared with `const` raises PHP's through the `#[\Deprecated]` attribute; a constant set with `define()` takes the tag alone, since nothing raises a notice for it:

```php
/**
 * Returns the most records the plugin keeps.
 *
 * @api
 *
 * @since       1.0.0
 * @version     1.1.0
 * @deprecated  1.1.0 Use dws_tpl_get_record_limit().
 *
 * @return      int
 */
function dws_tpl_get_limit(): int {
	_deprecated_function( __FUNCTION__, '1.1.0', 'dws_tpl_get_record_limit()' );
	return dws_tpl_get_record_limit();
}

/**
 * The most records the plugin keeps.
 *
 * @api
 *
 * @since       1.0.0
 * @version     1.1.0
 * @deprecated  1.1.0 Use DWS_TPL_RECORD_LIMIT.
 *
 * @var         int
 */
#[\Deprecated( 'use DWS_TPL_RECORD_LIMIT', since: '1.1.0' )]
const DWS_TPL_LIMIT = 10;
```

2. Move the plugin's own calls to the replacement in the same release; PHPStan reports each use that remains.
3. Add a changelog fragment with `composer changelog:add` that names the old symbol and its replacement, with a minor significance and the Deprecated type.
4. In the next major, delete it, with a major fragment that names it as removed.

## Distribution through wordpress.org

A plugin listed on wordpress.org gets its updates and its language packs from there, so it drops the GitHub updater's header and adds the directory's `readme.txt`.

1. In the entry file, delete the `Update URI` header, which turns the bundled updater inert, the `Tested up to` header, which only that updater reads and Plugin Check refuses beside a `readme.txt`, with `EntryHeadersTest`'s row for it, and the `load_plugin_textdomain()` line, since language packs load just in time.
2. Add `readme.txt`, whose `Stable tag` the release checks against the `Version` header, whose floors repeat the entry file's headers, and whose changelog block `composer changelog:write` fills:

```text
=== DWS Plugin Template ===
Contributors: TODO
Tags: TODO
Tested up to: TODO, the newest WordPress branch the plugin is tested on
Stable tag: TODO, the Version header
Requires at least: TODO, the Requires at least header
Requires PHP: TODO, the Requires PHP header
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

TODO: the plugin's one sentence, at most 150 characters.

== Description ==

TODO: what the plugin does, for the directory's listing.

== Installation ==

1. Install the plugin from Plugins → Add New Plugin.
2. Activate it.

== Changelog ==

<!-- Start changelog -->

<!-- End changelog -->

[See the full changelog on GitHub.](https://github.com/ahegyes/wordpress-plugin-template/blob/trunk/CHANGELOG.md)
```

3. Wrap `CHANGELOG.md`'s version blocks in the same `<!-- Start changelog -->` and `<!-- End changelog -->` markers, require `pronamic/changelog-md-to-wordpress-plugin-readme-txt` in `require-dev`, and make `changelog:write` sync the file:

```json
"changelog:write": [
	"@php vendor/bin/changelogger write",
	"@php vendor/bin/pronamic-changelog-md-to-wordpress-plugin-readme-txt"
]
```

Composer hands the arguments after `--` to every command in the array, so the first release runs the two apart, and `CONTRIBUTING.md`'s first-release command becomes:

```sh
vendor/bin/changelogger write --amend
vendor/bin/pronamic-changelog-md-to-wordpress-plugin-readme-txt
```

4. Lint the file in `.github/workflows/quality.yml`:

```yaml
  readme-lint:
    name: readme.txt lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
        with:
          persist-credentials: false
      - uses: thetwopct/wporg-plugin-readme-linter@1f869aba959265ecc90935b2380c6569d56646ac # v1.0.2
        with:
          path: readme.txt
          fail-on: error
```

5. In `.github/workflows/release.yml`, pass `wp-org: true` to the reusable release. A tag then also deploys the zip and the `.wordpress-org/` assets to the directory's SVN from the `wp-org-release` environment: create it, store `SVN_USERNAME` and `SVN_PASSWORD` there and only there, and protect it with required reviewers.

The wordpress.org build runs Plugin Check's full set but for the checks `exclude-checks` names; Plugin Check's `prefixing` and `direct_file_access` stop at PHP 8.4 syntax such as `protected(set)`, which wordpress.org's own scan may meet too. The bundled updater ships inert under `vendor-prefixed/`, which wordpress.org's reviewers may still ask to remove.

## Without WooCommerce

A plugin that does not need WooCommerce drops the WooCommerce package, the WooCommerce demo and its tooling, and keeps its settings on a WordPress options page.

1. In `composer.json`, delete the `vcs` repository entry whose `url` is `https://github.com/ahegyes/wp-framework-woocommerce`. Then remove the WooCommerce packages, which leaves every other locked version as it is:

```sh
composer remove --dev --ignore-platform-req=php+ --no-interaction ahegyes/wp-framework-woocommerce php-stubs/woocommerce-stubs wp-plugin/woocommerce
```

2. In `phpstan.dist.neon`, delete the WooCommerce stubs from `scanFiles`:

```neon
		- vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php
		- vendor/php-stubs/woocommerce-stubs/woocommerce-packages-stubs.php
```

In `phpstan.safeguards.neon`, delete the whole `scanFiles` key, which lists only those stubs, with its comment.

3. In `dws-plugin-template.php`, delete the `Requires Plugins`, `WC requires at least` and `WC tested up to` headers, and in `tests/Unit/EntryHeadersTest.php` the row that ties `WC tested up to` to the locked WooCommerce.

4. In `functions.php`, delete the `FeaturesUtil` import and the `before_woocommerce_init` closure, so the hooks after the includes loop read:

```php
// ... the includes loop ...

// The hooks come last, so an include that throws leaves nothing of the plugin hooked.
add_action(
	'plugins_loaded',
	static function (): void {
		dws_tpl_plugin()->boot();
	},
	15
);
register_deactivation_hook(
	DWS_TPL_FILE,
	static function (): void {
		dws_tpl_plugin()->deactivate();
	}
);
```

5. In `src/Plugin.php`, log through core's `ErrorLogLogger`, which drops records below warning unless `WP_DEBUG` is on, so `DWS_TPL_DEBUG` goes. Compose without the WooCommerce check, and leave the greeting out of `COMPONENTS`:

```php
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\AdminNotice\AdminNotices;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Logging\ErrorLogLogger;
use DeepWebSolutions\PluginTemplate\Scoped\DeepWebSolutions\Framework\Core\Migration\MigrationRunner;
// ...

	public const array COMPONENTS = array(
		Scheduler::class,
		AdminNotices::class,
		SettingsPage::class,
		Blocks::class,
	);

	/**
	 * The scheduler, which the components and the deactivation callback share.
	 // ...
	 */
	public readonly Scheduler $scheduler;

	// ...

	public function __construct() {
		$this->identity  = new PluginIdentity( DWS_TPL_FILE, self::SLUG, self::PREFIX );
		$this->logger    = new ErrorLogLogger( $this->identity );
		$this->scheduler = new Scheduler( $this->identity );
		$this->boundary  = new PluginBoot( $this->identity, $this->logger );
	}

	// ...

	/**
	 * Registers the scheduler and the migrations, then builds the kernel and boots the plugin's components.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  bool
	 */
	protected function compose(): bool {
		$this->scheduler->register_hooks();
		$this->migrations = new MigrationRunner( $this->identity, Migrations::get_steps(), $this->logger, $this->scheduler );
		$this->migrations->register_hooks();

		$this->kernel = new PluginKernel( new ContainerBuilder()->addDefinitions( $this->get_definitions( $this->identity, $this->logger, $this->scheduler ) )->build() );
		$this->kernel->boot( self::COMPONENTS );
		return true;
	}
```

6. Delete the greeting: `src/Greeting/`, `includes/greeting.php`, `tests/Integration/Greeting/` and `assets/src/greeting.scss`, then its entries in `package.json`:

```json
"build:assets": "wp-scripts build editor=./assets/src/editor.ts --output-path=assets/build",
"start:assets": "wp-scripts start editor=./assets/src/editor.ts --output-path=assets/build"
```

7. Delete `src/Settings.php`, and add the settings page from [A WordPress settings page](#a-wordpress-settings-page), with the consent to delete the plugin's data as a second field of its tab, under the option `uninstall.php` already reads:

```php
			'general' => array(
				// ... the limit ...
				array(
					'type'    => 'checkbox',
					'id'      => Options::DELETE_DATA,
					'title'   => __( 'Remove all data on uninstallation', 'dws-plugin-template' ),
					'desc'    => __( 'Delete the plugin\'s data when it is uninstalled, so it must be configured again if it is installed later', 'dws-plugin-template' ),
					'default' => Options::DEFAULTS[ Options::DELETE_DATA ],
				),
			),
```

8. In `src/Options.php`, delete the greeting's three keys with their defaults, and add the settings page's limit with its default. `src/Migrations.php` stays as it is, since its first step stores every default in `DEFAULTS`. In `tests/Unit/MigrationsTest.php`, the fingerprint in `SEEDED_KEY_SETS` becomes the one the suite's failure names, since no release has stored the old keys.

9. In `uninstall.php`, delete the `MetaTypes` import and `$dws_tpl_meta_types`, so the engine runs without WooCommerce's meta types:

```php
	Uninstaller::run(
		$dws_tpl_identity,
		$dws_tpl_footprint,
		array(
			'consent' => array( 'option' => $dws_tpl_consent ),
		)
	);
```

10. In `.wp-env.json`, `.wp-env.tests.json` and `.wp-env.multisite.json`, delete the WooCommerce mapping, and let `afterStart` only activate the plugin, network-wide in the multisite one; `.wp-env.json`'s command takes no `--config`:

```json
"mappings": {
	"wp-content/plugins/dws-plugin-template": "."
},
"lifecycleScripts": {
	"afterStart": "node_modules/.bin/wp-env --config .wp-env.tests.json run cli wp plugin activate dws-plugin-template"
}
```

11. Adapt the tests:
- Delete `tests/EndToEnd/without-woocommerce.spec.ts` and `tests/Support/OrderStorageTrait.php`.
- `OptionsTest` and the Integration `MigrationsTest` read and seed `Options::LIMIT` where they read the greeting's keys, and a malformed limit reads its default 1.
- `UninstallTest`, `PartialDeployTest` and `MultisiteTest` keep their consent rows, since the consent keeps its option; `UninstallTest` seeds and asserts no Action Scheduler action.
- `PluginBootTest` expects nothing in `$plugin->kernel->skipped`, and drops the `DWS_TPL_DEBUG` row with its `get_threshold()` helper and the `WooCommerceLogger` and `RunInSeparateProcess` imports.
- `SettingsTest` builds `new OptionsPage( $identity, Plugin::PREFIX, new SettingsPage( $identity ) )` over `new PluginIdentity( DWS_TPL_FILE, Plugin::SLUG, Plugin::PREFIX )` and renders it through `render_page()`, which first needs `wp-admin/includes/template.php`. It saves as `options.php` does: with `$GLOBALS['pagenow']` set to `options.php` and `$_REQUEST['option_page']` to `dws_tpl_general`, it calls `register_settings()`, then `update_option()` for each of the tab's options, with null for one the form did not post. `tearDownAfterClass()` removes each field's `pre_update_option_` filter and the group's `option_page_capability_` filter, and unregisters each setting.
- `tests/EndToEnd/settings.spec.ts` drives the page's limit field at `options-general.php?page=dws_tpl` with the same keyboard checks, scoping axe to `form[action="options.php"]`, and expects WordPress's "Settings saved." after each save.

12. Update `AGENTS.md`, `README.md` and `docs/ARCHITECTURE.md` wherever they name WooCommerce, the greeting, the settings tab or the order-tables declaration.
