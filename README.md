# D-bug

D-bug is a library that makes debugging PHP applications easier and less error prone.

## Features

* Gives detailed insight into complex objects
* Non-recursive mode won't choke on objects or arrays that contain references
* Provides basic protection against accidental use in a production environment
* Supports and automatically detects both web and CLI applications
* Clean, formatted output
* Colorization in non-recursive mode

## Basic Usage

Include the library somewhere in your project, preferably in the first file that is loaded.

```php
require_once('d-bug.php');
```

Debug a variable *non-recursively*

```php
D::bug($yourVariable);
```

Debug a variable *recursively*

```php
D::bugR($yourVariable);
```

Debug a class

```php
D::bugClass('YourClass');
```

Generate a backtrace

```php
D::bugBacktrace();
```

Run D-bug on itself to learn more about its capabilities

```php
D::bugMe();
```