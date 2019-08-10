# Watch Phpunit

## Installation

```
composer require backendtea/watch-phpunit --dev
```
## What is this
The intention of this application is to 'watch' for file changes, 
and run the tests related to files that have been changed since the last git commit.
This does mean that in its current form it needs to be in a git repository.

It figures out what classes depend on each other, and then runs the tests related to the 
changed files.


## Usage
```
$ vendor/bin/watch-phpunit watch --src ./src --test ./tests
```
(src and tests are the default values, so if your folder structure matches this, you can run it like so:)
```
$ vendor/bin/watch-phpunit watch
```
