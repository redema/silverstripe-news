# SilverStripe news Module

This module is the result of combining code and lessons learned from news
functions for a couple of different of sites which we have built at Redema.

It is supposed to be easy to integrate on new sites, easy to customize and
should perform well with big news archives.

Be aware that the module is far from complete and that backward incompatible
changes might be introduced in the future.

## Maintainer Contact

Redema AB <http://redema.se/>

Author: Erik Edlund <erik.edlund@redema.se>

## Requirements

 * PHP: 5.2.4+ minimum.
 * SilverStripe: 2.4.7+ minimum.
 * Modules: handyman.
 
## Installation Instructions

 * Install the required modules.

 * Place this directory in the root of your SilverStripe installation. Make sure
   that the folder is named "news" if you are planning to run the unit tests.

 * Visit http://www.yoursite.example.com/dev/build?flush=all to rebuild the
   manifest and database.

## Usage Overview

TODO.

## TODO

 * Write `Usage Overview`.
 * Implement tags and categories?
 * Decide if `NewsWeight*` really makes sense.
 * Make it possible to have a `NewsHolder`, which is a child of another
   `NewsHolder`, redirect requests to view it to the topmost `NewsHolder`.

