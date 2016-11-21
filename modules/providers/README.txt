Anatomy of a Yild Provider
--------------------------
--------------------------

What is a provider?
--------------------------
A provider provides the necessary search results from its own api to the Yild
module. This is because the process for searching for a term in freebase,
wikipedia and musicbrainz are vastly different. The job of the provider is to
perform the search and package it in a way that Yild understands.

Think of the provider as a black box: it does what you want it to do and Yild
trusts that the provider knows its job and returns the correct result.


Naming convention
--------------------------
In order to function as a Yild provider, the module should be named
yild_#PROVIDERNAME#.

Take care to choose a name that identifies the provider, but doesn't contain
any spaces or special characters. Examples of good module names are:
yild_freebase, yild_wikipedia etc.

Hooks
--------------------------

hook_yild_search:

For your provider to work, you have to implement certain hooks. For instance,
you must implement a search hook called hook_yild_search. For freebase, this
hook would be named "yild_freebase_yild_search". What the hook does is simply
searches the relevant provider using its api for the term given and returns the
result formatted in the way yild requires.

Two values are passed to hook_yild_search: $search_string and $lang. These
contain the string to search for from the provider's api, as well as the
language code for the language to search in.

hook_yild_get_providername:

Simply returns the name of the provider so the base module knows which provider
in particular that module acts as.

Caching
--------------------------

All or most pre-packaged providers cache the results for one day to minimize
traffic. At first, it will be each provider's task to cache as needed. Perhaps
this should be made a user interface configurable setting in Drupal.
