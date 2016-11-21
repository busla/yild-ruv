YILD
--------------------------
--------------------------

Yild stands for "Yild integrator for Linked Data" and is a recursive acronym in
the same spirit as many others. Y could also theoretically stand for the
Finnish Broadcast Company (Yle) where I work, but that's not the official story.

Yild is used for connecting a drupal taxonomy term reference field to one or
several providers.

This has a few benefits: One, you will connect your content to "official"
ontologies that others in the world are using, so you will actually be tagging
your articles with the same Rick Astley that your peers are using and in the
future, some kind of data linking might be possible where your Rick Astley
content is linked to others. Neat, huh?

Also, it will guarantee you are (mostly) using the same Rick Astley to tag all
your articles. Freehand tagging is susceptible to typos, syntactic inconsistency
and other issues. Of course the large amount of terms in a big ontology opens
up many possibilities for interpretation: do you want to use "wars", "warfare"
or "conflict resolution".

Using external ontology providers also makes it possible to annotate your
content with RDF markup linking to well-known data sources, which Google and
other search engines really like.


How it works?
--------------------------
In the beginning, you will have a sort of "all or nothing" situation. You can
link a taxonomy field to all enabled providers or just one.

Connecting Yild to a taxonomy term reference field is done using a widget. Out
of the box you will always have the widget "Yild Default autocomplete". This one
will use all your enabled providers and provide search results from them all
using the same keyword.

Every provider also defines a provider specific widget for connecting a Drupal
field to only one provider. These are named by the provider, so the widget for
Freebase is called "Yild Freebase autocomplete".

One you connect a field and create new content using a term reference field with
a Yild widget, you will se an autocomplete menu prefixed with the provider's
name, as well as a number indicating how many times that same term has been used
before on your site. This makes it easy to use the same terms about the same
subjects.


Providers
--------------------------
Providers are modular packages that provide connectivity to one particular
ontology service, such as Freebase.

Providers are Drupal modules themselves and live in the folder
yild/modules/providers. This solution is close to what you see in Skald and
other Drupal projects. This makes it very easy for you to write your own
provider.

The Providers that are prepackaged are Freebase, Wikipedia, Dbpedia and Finto.
If the provider provides any kind of Api interface, it will be an easy task to
implement a provider module for Yild.

What also needs to be user configurable in the future is provider specific API
keys.


Configuration
--------------------------
For the first version, only search language can be configured. The
ISO language string is passed to each module's hook_yild_search. It falls upon
each provider to search in a way that returns the desired language. For some
providers this is simply not possible, if they do not provide any data in the
selected language. In that case, you need to use a language variable that works.

In the future, a provider by provider language setting might be implemented and
as per Drupal's configuration framework, it's already quite possible for each
provider module to have their own configuration page.
