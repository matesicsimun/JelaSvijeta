## Jela Svijeta

### First time setup
To install the needed libraries run `composer install`.<br>
To create the SQLITE database run `doctrine:migrations:migrate`.<br>
To fill the database with fake data run `php bin/console app:fill-db`.<br>
To clear the database run `php bin/console app:clear-db`.


### Using the API
The app exposes one API: `/dishes` which allows for multiple parameters.
The available params are:
<ul>
    <li>lang (required) - Specify language ('hr', 'fr', 'de'...)</li>
    <li>page (optional) - Specify page number</li>
    <li>per_page (optional) - Specify number of dishes per page</li>
    <li>category (optional) - Search dishes by category (!NULL, NULL - search for dishes that either have, or have not a category assigned</li>
    <li>tags (optional) - Search dishes by tags (one or more tags)</li>
    <li>with (optional) - Select which dish properties are returned</li>
    <li>diff_time (optional) - Select all dishes (including deleted) that have been modified, created or deleted after the timestamp</li>
</ul>
