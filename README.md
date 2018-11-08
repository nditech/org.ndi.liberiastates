# org.ndi.liberiastates
Add Liberia Counties (which are like states in Civi)
# Install custom states

This extension allows you to install new states within CiviCRM.

## Configuring your states

Edit `[this extension name].php` to set the array of configuration parameters.  The function `[this extension name]_stateConfig()` defines and returns an array with four items:

 * `overwrite` is whether to overwrite the existing state options.  If `overwrite` is `TRUE`, any state in CiviCRM in the country you select will be deleted unless it is listed in the `states` or `rewrites` below.  **Do not** use `overwrite` to `TRUE` on a site with existing real data.
 * `countryIso` is the [ISO abbreviation](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) for the country you're covering.  The extension uses this to look up the country in the `civicrm_country` table.
 * `states` is the list of states you want to maintain.  It should be an array: each item's key is the full name, and the value is the state's abbreviation.  If you are just adding certain states to CiviCRM and have `overwrite` set to `FALSE`, you do not need to add every existing state to the list.
 * `rewrites` is an array of state names to rewrite.  The array keys are the names to look for, and the array values are the names to set them as.  The rewritten state should be in the `states` array.  *The abbreviation will not be rewritten*.

### Example: small adjustments

This extension will add to United States the new states of [Franklin](https://en.wikipedia.org/wiki/State_of_Franklin), [Jefferson](https://en.wikipedia.org/wiki/Jefferson_%28proposed_Pacific_state%29), and [New Columbia](https://en.wikipedia.org/wiki/District_of_Columbia_statehood_movement), the latter being a rewrite of the District of Columbia.

```php
function myExtension_stateConfig() {
  $config = array(
    'overwrite' => FALSE,
    'countryIso' => 'US',
    'states' => array(
      'Franklin' => 'FR',
      'Jefferson' => 'JF',
      'New Columbia' => 'DC',
    ),
    'rewrites' => array(
      'District of Columbia' => 'New Columbia',
    ),
  );
  return $config;
}
```

### Example: wholesale replacement

This extension will replace the listing of states/provinces in the United Kingdom.  The default list of counties, council areas, etc. will be removed, and the constituent countries will be added.

```php
function myExtension_stateConfig() {
  $config = array(
    'overwrite' => TRUE,
    'countryIso' => 'GB',
    'states' => array(
      'England' => 'ENG',
      'Northern Ireland' => 'NIR',
      'Scotland' => 'SCO',
      'Wales' => 'WLS',
    ),
    'rewrites' => array(),
  );
  return $config;
}
```

## Expected behavior

Install with no rewrites, overwrite false:

- all the original states will still be there
- new states will be added where they differ from the existing states.

Disable and re-enable the extension: there will be no change so long as the code has not been changed.

Install with overwrite true, no rewrites:

- the only original states retained will be those that match the new states
- new states will be added where they differ from the original states

Install with overwrite true, plus a handful of rewrites:
- the only original states retained will be those that match the new states
- rewritten states will retain their ID numbers
- new states that neither match the old states nor are a rewrite will be added
