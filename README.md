# gitlab-migrate-issues

This app will assist with the migration of issues from one GitLab server to another. The export feature in GitLab CE doesn't restore the labels or if it's closed or not.

## WARNING

This source code was written as fast as possible and as such will have many areas where improvements can be made, better coding standards utilised and obvious optimisations added.

Although this worked flawlessly for me, it may break your site. Please ensure you make a backup of both servers before you use this (or any other migration tool).

## What it does

- Checks you have all the labels and these have the correct colors
- Loads all the open tickets into a list
- Fetches all comments/notes made to an issue
- Fetches the labels associated to the issue
- Creates all the issues on the new server
- Adds all the notes/comments to the new issues
- Applies the labels (if required) to the newly created issue

## Usage

You will need to generate an Access Token on both the originating server and the destination.

1. Click on "Settings"
2. Access Tokens
3. Enter the name "Issue migration" with an expiry in the future (i chose 3 days), choose the role `Reporter` and check `read_api` and `api` permissions.

... when you click on `Create project access token` you'll be shown your access token code.

Change the command below setting:

- FROM_PROJECT_ID - integer, found on the project "settings", "General" page (Project ID). 
- FROM_HOST - URL, `oldgitlab.mydomain.com`
- FROM_TOKEN - see above how to generate this.
- TO_PROJECT_ID - integer, found on the project "settings", "General" page (Project ID). 
- TO_HOST - URL, `newgitlab.mydomain.com`
- TO_TOKEN - see above how to generate this.

```
docker build -t gitlab-migration ./
docker run --rm -it -v ./src:/usr/local/bin/src gitlab-migration:latest
docker run --rm -it --env FROM_PROJECT_ID=change-me --env FROM_HOST=change-me --env FROM_TOKEN=change-me --env TO_PROJECT_ID=change-me --env TO_HOST=change-me --env TO_TOKEN=change-me gitlab-migration:latest
```

## Other uses

This code can be rapidly modified to interface with the new/old or both GitLab servers to do much more... get analytics, submit issues/notes/comments from your control panel, etc.

## Issues and things it doesn't do

- If you run it twice it will duplicate issues (it wont detect that it already exists).
- Doesn't retain milestones
- Doesn't retain author of issues
- Doesn't retain author of notes on issues