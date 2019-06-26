# Watch Phpunit

## What is this
The intention of this application is to 'watch' for file changes, 
and run the tests related to those files.

In its current implementation it does so very naively. 
The app can be ran with two options, the source folder to watch, and the test folder.
If any of the files in these folder change it will attempt to run the tests.

If the file is in the test folder it will simply run phpunit with that test.
If the file is int he source folder, then it will look for the file as follows:

```diff
+ src/Folder/FileThatChanged.php
- tests/Folder/FileThatChangedTest.php
```


## Usage
```
$ vendor/bin/watch-phpunit watch --src ./src --test ./tests
```
(src and tests are the default values, so if your folder structure matches this, you can run it like so:)
```
$ vendor/bin/watch-phpunit watch
```
