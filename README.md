#CSS Parser and Report Generator

This project was built for PHP versions 5.4 or higher.
It was also meant to be deployed to Heroku, which generally
has an ephemeral filesystem. As such, this application requires some
configuration with Amazon S3.

Namely, you will need to specify the ACCESS_KEY, ACCESS_KEY_SECRET, and S3_BUCKET name
in the environment in order for this to work.

##On The Nature of the Problem
This is an extremely crude (and at times, wrong) CSS parser. Since the parser
was not done using a lexer or traditional grammar parsing tool (here we used regex magic),
this really only works with a subset of well-formed CSS. Comments and literal close brackets
will break the parser horribly, since a regex is an incredibly blunt tool for this problem.

That said, the parser is capable of exposing all parts of the CSS document
to the PHP environment in a language-native format.

##On Scaling Up
I'm not sure if it counts as cheating or not, but I used S3 as the persistent storage
backend for the CSS / reports generated. The other option using pure PHP would have involved
writing the file to local disk, but if this service were to scale, I would need to replicate
that disk write to all servers in the cluster to keep the reports consistently available. In that
way, using a remote storage service which deals with all of the replication for me makes a lot of sense.
This also deals with the problem of having to scale out the ability to download the report, as S3's
infrastructure basically allows us to turn a dial and build our more capacity without having to rack
our own infrastructure.

Also, this application currently reads the entire file into memory at once. This is *bad* for scalability,
but works well enough for the limitations we've put in place for now (5MB file size limit). Really,
if a stylesheet is much larger than that, it doesn't belong on a web page to begin with, so I consider
this decision to be practical.

Both the client and the entrypoint should check the filesize to make sure we aren't being attacked by
some malicious user uploading large files.

Currently, this implementation should be safe from attacks which try to read private resources from disk,
as well as attacks which try to write content to malicious paths (i.e. read /etc/passwd).

##Some Design Decisions
There are some pretty pervasive design decisions that I consciously made when constructing this.
One of the more confusing ones is perhaps the convention of ascribing a unique Exception class to each
exception throw site. I'm a big fan of this for the reason that it can often make logging and reporting
much simpler, and it becomes easy to detect problem sites when a particular type of exception spikes. In
the future, these exceptions would extend from some class that is "HTTP-aware," so to speak, and encapsulate
things like HTTP status code to return (if caught at the highest level), JSON payload of error (if in API),
etc.

I'm also generally very careful about array access here, favoring an isset() check of an array key
before doing an explicit read when practical. This allows us to enable E_STRICT validation in our
environment which generally encourages safer PHP practices.