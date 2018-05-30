THIS IS INCOMPLETE!!!!
============

SC2 Ranked Stats
============

"SC2 Ranked Stats" is a set of open source commands being used by a Discord bot "Taeja" (name may change) for viewing current season ranked details and statistics on players and clans in Starcraft 2.


Purpose
============

Anyone can suggest edits/pull requests for new features.

Discord Command List
============
```bash
list all commands here...
```

Discord Command Usage
============
```bash
~player <player name here>
```

<img src="https://i.imgur.com/Do0AJk2.png" width="600px" height="450px"></img>






How It Works
============

Rather than querying Blizzard's API everytime the bot is used, an hourly job executes a script to update a local database which is where the bot collects its data from.

With this system, the bot can provide more generalized and insightful view on data that would otherwise be near-impossible to classify from a straightup query of Blizzards API.

Categorization and querying of Clan Tags, Player Names, Player Battle Tags are not possible with Blizzards API alone.
