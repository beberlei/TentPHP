# PHP Tent Client

Tent Client API for PHP

Tent.io is a distributed protocol for social networking. There are tent servers
and tent applications.  Users host their data on tent servers. Tent
applications can modify this data. To implement a tent application you need a
client. This library provides a client written in PHP.

The client has to act as an application to be able to access user details on
any tent server. to work applications are always required to maintain some level of
state about Tent Servers Url and their OAuth Client Ids and Mac Keys.

This application state is hidden behind a persistence interface
``TentPHP\Persistence\ApplicationState``. We are shipping a Doctrine DBAL
based implementation.
