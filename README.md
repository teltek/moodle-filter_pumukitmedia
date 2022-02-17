# PuMuKIT Personal Recorder filter

This filter will replace any link generated with PuMuKIT repository with an iframe that will retrieve the content served by PuMuKIT.

## How to install

### Step 1: Clone the latest code version from GitHub
```
git clone https://github.com/teltek/moodle-filter_pumukitpr pumukitpr
```

### Step 2: Create .zip to install

Move to downloaded folder and execute the following command.
```
zip -r moodle-filter_pumukitpr.zip pumukitpr -x "pumukitpr/.git/*" -x "pumukitpr/.github/*" -x "pumukitpr/.gitignore"
```

### Step 3: Upload, configure and activate

Upload .zip on Moodle -> Administration -> Plugins -> Install.

Configure the plugin with your [PuMuKIT data password](https://github.com/teltek/PumukitLmsBundle/blob/master/Resources/doc/Configuration.md)

Save and activate the filter, it will be ready to use.
