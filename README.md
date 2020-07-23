# BambooHR -> Moodle Integration

## Field mapping
Map BambooHR account fields to Moodle account fields.

Where the BambooHR field is a _list_ and the Moodle field is a _menu_, the _menu_ items will be pulled from the BambooHR _list_ options.

Where a User context role is selected for the Supervisor, the Supervisor's Moodle user will be assigned the specified role in the context of their direct reports.

## References
* BambooHR documentation https://documentation.bamboohr.com/docs
* Moodle example supervisor role https://docs.moodle.org/36/en/Parent_role

## Changes
### 20200724
* Remove dependency on BambooHR employeeId in idnumber
* Remove individual record sync, fix supervisor assignment
