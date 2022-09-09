# ServiceLine Backend Engineering Test

## Task Description
There are a few items that I have made assumptions in processing

- If the CustomerNumber (from the customer import CSV) is invalid, it reads the highest number in the DB and creates a new record based on this.  Since there were no instructions on unique emails or names (only CustomerNumber), there is a chance that a customer could potentially be entered twice with a new number.  This could be rectified by adding unique values to email or name and not allowing a subsequent record to be inserted into the db; or by skipping the record all together and only looking for NULL values for new record insertion.

## General Notes
- ~~Assume the program will be run multiple times.~~
- ~~Assume this is a multi-tenant database where multiple users will store their data.~~
- ~~It can be assumed all users, customers, and locations are based in the United States.~~
- ~~You're free to include any open source libraries you'd like to make the task easier.~~
- ~~Please contain the answer to a single file named `importer.php`. You're free to use any architectural style you wish.~~
- ~~Empty values should be inserted as `null` wherever possible.~~
- ~~Create indexes on the `customer` and `location` tables where necessary - assume these can get quite large.~~
- ~~The following command will be used to execute the script:~~

```bash
php importer.php AAA1-Customers.csv AAA1-Locations.csv
```

- ~~Output an error if either filepath passed in as arguments to the script does not exist.~~
- ~~Assume any additional files passed into the script will follow the same format.~~

## Customer Notes
- ~~A unique, single column, primary key is required.~~
- ~~A `customer` record should be created if it does not already exist based on the "CustomerNumber" column, updated otherwise.~~
`-- Please see above notes`
- ~~A valid `customer` record requires either a non-empty "FirstName" or non-empty "LastName" value.~~
- ~~Invalid email addresses should be nullified.~~
- ~~Invalid phone numbers should be nullified.~~
- ~~Valid phone numbers should be formatted as (XXX) YYY-ZZZZ where X, Y, and Z are all digits.~~
- A phone number must have a real United States area code to be considered valid.
`-- Did not download a valid area code database to perform check`
- ~~The column in the `customer` table that stores the "Birthday" data should be a date and only store valid dates.~~
- ~~Manually deleting a `customer` record should delete all associated `location` records.~~
`-- This works but only if the following SQLite Command is run (seems to be a limiting factor on SQLite)`

```bash
PRAGMA foreign_keys=ON
```

## Location Notes
- ~~A unique, single column, primary key is required.~~
- ~~A `location` record should be created if it does not already exist based on the "LocationNumber" column, updated otherwise.~~
`-- Please see above notes, this falls into the same category as CustomerNumber above`
- ~~Each `location` record must have a relationship to a `customer` record.~~
- ~~A `customer` record can have more than one `location` records.~~
- ~~A valid `location` record requires a non-empty "StreetAddress" value.~~