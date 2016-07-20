# Flag Comments

This module extends the comments module core functionality and adds the ability for a front-end user to flag a comment.

When a comment has been flagged, it cannot be flagged by anyone else.

## Installation

`composer require micmania1/silverstripe-flagcomments`


## Configuration

Assuming you've already added comments to your page [as per instructions](https://github.com/silverstripe/silverstripe-comments/blob/master/docs/en/Configuration.md)
than all you need to do is enable flagging via config and add the flag to your template.

```
SiteTree:
  extensions:
    - 'FlagCommentsExtension'
  comments:
    can_flag: true
```


Adding the flag to your template:

The endpoint expects an ajax request and only responds with JSON. You'll have to handle this yourself, but to add the flag to your HTML you need to edit the `CommentInterface_singlecomment.ss` template. There's no reason why you can't add this to another template (this is just a helping hand).

```
<% if $canFlag %>
	<a href="$FlagLink">Flag</a>
<% end_if %>
```
