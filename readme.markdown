# MailChimp Scripts #
Cal Evans - cal@calevans.com

http://blog.calevans.com


This repo is home to the occasional script I write to manipulate lists on mailChimp. Use at your own risk.

## listSegmentMaker ##
This script allows you to make a static segment for a list based on the opens from campaigns whose names contains the query.

### Sample Usage ###

    php listSegmentMaker.php -a="Your API Key" 
                             -l="Your List ID"
                              -q="dc4d#4" 
                             -s="Your segment ID"
                             -v 

Assuming you put in the proper list and segment ID, this would take the email addresses from all the campaign's whose name contained dc4d#4 and add them to the static segment specified.

The options include:

Fetch and validate the cli options
 
    -l = listId. Required for all operations except for -g
    -s = segmentId. If it does not exist then create it 
    -n = Segment name. Require for new segments, optional for existing segments.
    -q = The query to look for in the campaign name.
    -f = fetch list of segments and IDs for a given list
    -g = fetch list of lists
    -v = verbose mode. shows what is going on.
    -t = test mode. doesn't actually do anything.
    

Copyright (c) 2012, E.I.C.C., Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
