**Some commands have been temporarily ommited from this repository as I'm going through a complete refactorization and recode of this project before pushing the new changes.**

TaeJa Bot
============

TaeJa Bot is a Discord Bot for Starcraft II clans. It runs on the <a href='https://github.com/vsTerminus/Mojo-Discord'>Mojo::Discord</a> library. 

TaeJa Bot can help manage your clan or simply for viewing player statistics.


Discord Command List
============
```bash

```

Discord Command Usage
============
```bash

```


How It Works
============

Rather than querying Blizzard's API everytime the bot is used, an hourly job executes a script to update a local database which is where the bot collects its data from.

With this system, the bot can provide more generalized and insightful view on data that would otherwise be near-impossible to classify from a straightup query of Blizzards API.

Categorization and querying of Clan Tags, Player Names, Player Battle Tags are not possible with Blizzard's API alone.
