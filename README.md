# PuMuKIT Personal Recorder filter

This filter will replace any link generated with pumukit repository with an iframe that will retrieve the content served by pumukit.

## How to install

### Step 1: Download the latest code version from GitHub
```
https://github.com/teltek/moodle-filter_pumukitpr
```

### Step 2: Create .zip to install

Move to downloaded folder and execute the following command.
```
zip -r  moodle-filter_pumukitpr.zip moodle-filter_pumukitpr -x "moodle-filter_pumukitpr/.git/*" -x "moodle-filter_pumukitpr/.github/*" -x "moodle-filter_pumukitpr/.gitignore
```

### Step 3: Upload and configure

Upload .zip on Moodle -> Administration -> Plugins -> Install.

Configure the plugin with your [PuMuKIT data password](https://github.com/teltek/PumukitLmsBundle/blob/master/Resources/doc/Configuration.md)

Save and the filter will be ready to use.
