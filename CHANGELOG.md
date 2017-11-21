### 2.1.0 (2017-11-21)
  * Add rounding option to multiply and divide operations

### 2.0.0 (2017-11-13)
  * PHP 7.1 is the minimum requirement
  * Configurable scale
  * Rounding now changes the scale of the number
    * Before: '0.0050'->round(2) => '0.0100'
    * Now: '0.0050'->round(2) => '0.10'
  
### 1.1.5 (2017-10-02)
  * Fix precision issues with floats as arguments
  
### 1.1.4 (2017-05-18)
  * Fix negative rounding issues

### 1.1.3 (2015-07-08)
  * Throw an exception when dividing by 0 to avoid a warning (@Hyunk3l)
  * Fix scale errors in comparisons with 0
  * Fix typos and correct phpdocs

### 1.1.2 (2015-07-06)
  * Fix scale errors in the comparisons (@xeviplana)

### 1.1.1 (2015-03-03)
  * Fix typos in docs and docblocks
  * Add this CHANGELOG

### 1.1.0 (2015-02-27)
  * Add a custom InvalidArgumentException that extends from the base PHP InvalidArgumentException
  * More tests!
  * Fix some naming issues
  * Update docs

### 1.0.0 (2015-02-26)

  * First version!
