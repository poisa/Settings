[![Build Status](https://travis-ci.org/poisa/Settings.svg?branch=master)](https://travis-ci.org/poisa/Settings) 
[![codecov](https://codecov.io/gh/poisa/Settings/branch/master/graph/badge.svg)](https://codecov.io/gh/poisa/Settings)
[![Maintainability](https://api.codeclimate.com/v1/badges/2facfc8aaea8faeb5e45/maintainability)](https://codeclimate.com/github/poisa/Settings/maintainability)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/afb3c332-6fc9-4329-a933-2b7c244f467d/small.png)](https://insight.sensiolabs.com/projects/afb3c332-6fc9-4329-a933-2b7c244f467d) 

# Settings: A Laravel 5 multi-tenant settings manager

### Package objective
To be able to store custom configuration items in a database in a single or multi-tenant environment either in plain text, encrypted form, or any other customizable format. By configuration I am not referring to Laravel's configuration but rather to your domain-specific configuration. 

There are 3 specific scenarios where this package might come in handy:

1. Multi-tenant systems where you deploy the code to one server and it connects to a different tenant database depending on domain rules (eg. different users connect to different databases).
2. Same as #1 except you add a main database that you always connect to. Take a CMS for example, where you have the CMS's own database (a.k.a. the `system` database) and then you also connect to each of your client's database (a.k.a the `tenant` database). In this scenario you work simultaneously with both databases.
3. A single-tenant website with just one database. 

This package really shines when you need to store odd-ball data for which you would not necessarily want to create a separate table (think if it as a glorified key-value store).

