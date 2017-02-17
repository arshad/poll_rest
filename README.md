# poll_rest
Drupal 8 Poll + REST

### Installation
1. Enable poll, rest, restui and poll_rest modules.
2. Head over to `/admin/config/services/rest` and enable the poll vote resource.
3. Enable the POST method.
4. Save.

### Usage
Make a post request to `/api/v1/poll/1/vote?_format=json` with the following payload:

```
{
	"chid": 2
}
```
