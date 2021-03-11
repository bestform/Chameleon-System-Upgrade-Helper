Chameleon System Upgrade Helper
===============================

This bundle will add the command `chameleon_system:upgrade_helper` that will help you find access of non-public services when using the `Container` or the `ServiceLocator` 

Run it with a path to the root of the source files to check:

```
$ app/console ch:upgr "/usr/local/apache2/htdocs/customer/src"
```

It can produce two kinds of warnings:

1. Implicit call: This is the case when a service is aquired using a variable. In this case the parse can not determine if the service is available and public. This might produce false negatives. Every case should be checked by hand.
2. Calls to non existing/non public services. In this case the respective service should be made public.