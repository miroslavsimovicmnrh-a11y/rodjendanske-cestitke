# Lokalni mini sistem za rođendanske čestitke

## Instalacija

1. Instalirajte PHP 8 ili noviji.
2. U root direktorijumu pokrenite `composer require phpoffice/phpspreadsheet` kako biste dodali biblioteku za rad sa Excel fajlovima.
3. Napravite `.env` fajl sa sadržajem `OPENAI_API_KEY=vaš_ključ` (po želji dodajte `OPENAI_MODEL=` ako menjate model).
4. Proverite da `data/` direktorijum ima prava pisanja (npr. `chmod 775 data`).
5. Excel fajlovi za porudžbine i pitanja će se automatski kreirati pri prvom upisu (nije potrebno ništa ručno dodavati u repozitorijum).
6. Pokrenite lokalni PHP server komandom `php -S localhost:8000` iz root foldera projekta.

Aplikacija će biti dostupna na `http://localhost:8000/index.html`.
