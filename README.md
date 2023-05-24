# Laravel Query Augment

Let the example speak by itself.

```php
use Illuminate\Support\Facades\DB;

// usual mysql insert
DB::table('testing')
    ->insert([
        'text' => 'foo',
        'number' => 50,
    ]);

$item = DB::table('testing')
    ->where('number', 50)
    ->addSelectCase(function ($testing) {
        if ($testing->text == 'foo') {
            return 'yay!';
        }
    }, 'value')
    ->first();

$item->value; // 'yay!'
```

## How is this witchcraft working?
You probably noticed the `addSelectCase` method in the example above. Actually, this method is not invoked. The package compiles the source code inside the closure into a MySQL `CASE` statement. It sounds crazy, but it works!

The transpiled code looks something like:

```sqlite
select
    (case when testing.text = 'foo' then 'yay!' end) as 'value'
from "testing"
where "number" = 50
```

Only a small subset of PHP is allowed inside the closure.

