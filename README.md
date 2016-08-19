# phppgadmin
the premier web-based administration tool for postgresql

This is a fork of [phpPgAdmin](https://github.com/phppgadmin/phppgadmin) that implements **a lot of changes**

- the app was fully refactored adding namespaces, proper folder hierarchy, separating each class in its own file and stripping the use of require and include to the bare minimum
dropping support for PHP < 5.4.
- it provides full composer compatibility
- it has PSR-4 autoloading
- it makes requirement checks so you can't go wrong
