## 1.0.2 (August 05, 2020)
  - improved robot/files tab
  - minor

## 1.0.1 (August 03, 2020)
  - robot page: added software tab
  - increased minimum version of compose to v1.0.2
  - robot/info: replaced Swap usage with Battery status chart

## 1.0.0 (July 23, 2020)
  - architecture page now supports new theme
  - minor
  - minor

## 1.0.0-rc5 (July 21, 2020)
  - added compatibility data to metadata.json as per compose v1.0+ requirement
  - reformatted metadata.json
  - preparing transition to compose v1.0
  - fixed bug

## 1.0.0-rc4 (July 04, 2020)
  - added default mission
  - minor

## 1.0.0-rc3 (July 04, 2020)
  - fixed bug

## 1.0.0-rc2 (July 02, 2020)
  - cleared post_update

## 1.0.0-rc (July 02, 2020)
  - improved tab architecture in robot page
  - improved robot page
  - removed code-loader setup tab
  - added CPU frequency plot to health tab in robot page
  - removed ports from package configuration as we move towards device-proxy
  - improved robot page
  - updated port-update
  - minor
  - added devel version of robot page
  - removed mission control page
  - removed docker_host_root from configuration
  - minor
  - Merge remote-tracking branch 'origin/master'
  - added robot page
  - fixed bug with port in $_SERVER['HTTP_HOST']
  - updated path to databases to support new user-data
  - added support for multi-robot missions
  - Bump version to 0.2.1.
  - fixed bug

## 0.2.8 (October 07, 2019)
  - bug fixes

## 0.2.7 (October 04, 2019)
  - improved setup module

## 0.2.6 (October 01, 2019)
  - minor

## 0.2.5 (October 01, 2019)
  - added setup module
  - added device-loader configurable parameters

## 0.2.4 (September 23, 2019)


## 0.2.3 (September 23, 2019)


## 0.2.2 (September 23, 2019)
  - Bump version to 0.2.1.
  - fixed take over
  - Bump version to 0.2.1.
  - fixed bug

## 0.2.1 (September 23, 2019)
  - fixed take over

## 0.2 (June 04, 2019)
  - added Calibration module; minor
  - minor
  - minor

## 0.1.2 (April 24, 2019)
  - added auto-load and load-last to mission-control; added support for mission control menu
  - removed default mission from database, moved to post_install as public database using the package data

## 0.1.1 (April 21, 2019)
  - minor

## 0.1.0 (April 21, 2019)
  - added bump-version
  - added mission-control menu to mission-control page
  - moved getDuckiebotName to Duckiebot class
  - fixed race condition
  - added fall-back to http_host if duckiebot name is not set
  - mission-control can now be accessed only after login
  - minor
  - refactored code using ROS class
  - refactored code; moved stuff to duckietown/ros pkgs
  - renamed AIDO -> Duckietown
  - Update metadata.json
  - merged telemetry and drive into a single page
  - minor
  - added Drive page
  - added websocket settings to configuration file
  - updated dependencies
  - minor
  - added telemetry page
  - moved data to common package
  - first commit

