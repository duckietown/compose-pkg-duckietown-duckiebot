## 1.4.8 (September 10, 2021)
  - added support for DB21J/R robots
  - Merge remote-tracking branch 'origin/master'
  - Bump version to 1.4.5.
  - added support for new robot hardware

## 1.4.7 (July 11, 2021)
  - Bump version to 1.4.6.
  - Bump version to 1.4.5.
  - added duckietown_ros as deps
  - Bump version to 1.4.6.
  - Merge tag 'v1.4.5'
  - disabled permissions setup at first launch
  - Bump version to 1.4.5.
  - added support for new robot hardware

## 1.4.6 (July 11, 2021)
  - Bump version to 1.4.5.
  - added duckietown_ros as deps
  - Bump version to 1.4.5.
  - added support for new robot hardware

## 1.4.5 (July 11, 2021)
  - added duckietown_ros as deps
  - Merge remote-tracking branch 'origin/master'
  - Bump version to 1.4.3.

## 1.4.4 (May 21, 2021)
  - Merge branch 'master' of github.com:afdaniele/compose-pkg-duckietown-duckiebot
  - Merge branch 'new'
  - added support for new robot types

## 1.4.3 (May 21, 2021)
  - improved components tab

## 1.4.2 (May 20, 2021)
  - fixed bug in calibrations tab

## 1.4.1 (May 20, 2021)
  - minor
  - minor

## 1.4.0 (May 20, 2021)
  - added 'calibrations' tab to robot page

## 1.3.0 (May 19, 2021)
  - minor
  - added components tab to robot page

## 1.2.5 (March 22, 2021)
  - pages/robot/info: now showing Duckietown Disk image version instead of JetPack's

## 1.2.4 (March 20, 2021)
  - now asking for confirmation before shutting down/rebooting the robot

## 1.2.3 (March 20, 2021)
  - deleted page "code"

## 1.2.2 (March 20, 2021)
  - now using new files-api /data/ endpoint

## 1.2.1 (March 20, 2021)
  - now using disk directly when saving/reading robot settings, was using files-api before

## 1.2.0 (March 19, 2021)
  - pages/robot: now passing the trigger signal (aka value) when calling the health-api
  - added `Shutdown` and `Reboot` buttons to robot's page
  - permissions set by default to TRUE in firt setup; automatically skip the fist 2 steps of first setup of compose;
  - now using new files-api endpoint (e.g., /data/<files>)
  - minor

## 1.1.1 (March 01, 2021)
  - removed "files" tab in Robot's page, now using elfinder as file manager
  - data permission rewording

## 1.1.0 (January 21, 2021)
  - bumped compose dependency version
  - added `first_setup` module to `duckietown_duckiebot` package
  - added robot_settings/set API endpoint
  - fixed integration of new health API output; added subpage Robot/Settings
  - fixed bug in Duckiebot.php
  - Duckiebot class is now using files-api to read/write from/to the robot
  - implemented setter for permission/settings/autolab parameters
  - improved robot/settings tab
  - added robot/settings tab

## 1.0.4 (September 24, 2020)
  - added page `desktop`
  - page:robot/software: now using code api v1.1+

## 1.0.3 (August 11, 2020)
  - added favicon

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

