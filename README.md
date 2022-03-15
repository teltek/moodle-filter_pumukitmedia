# PuMuKIT Media filter

This filter will replace any link generated with PuMuKIT repository with an iframe that will retrieve the content served by PuMuKIT.

## How to install

### Step 1: Clone the latest code version from GitHub
```
git clone https://github.com/teltek/moodle-filter_pumukitmedia pumukitmedia
```

### Step 2: Create .zip to install

In the same folder where you do the last step execute the following command:
```
zip -r moodle-filter_pumukitmedia.zip pumukitmedia -x "pumukitmedia/.git/*" -x "pumukitmedia/.github/*" -x "pumukitmedia/.gitignore"
```

### Step 3: Upload, configure and activate

Access to moodle as Administrator and go to "Site administration" -> "Plugins" -> "Install plugins"

Upload moodle-filter_pumukitmedia.zip package and click in "Install plugin from the ZIP file". 

Follow the moodle instructions in the next sections until the configuration section.

Configure the plugin with your [PuMuKIT data password](https://github.com/teltek/PumukitLmsBundle/blob/master/Resources/doc/Configuration.md) and Save

Now, go to "Site administration" -> "Plugins" -> "Filters" -> "Manage filters"

Look for "Pumukit Media filter" and change it status from Disabled to On and it will be ready to use.
