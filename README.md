# Moduł płatności PayU dla WooCommerce
``Moduł jest wydawany na licencji GPL.``

**Jeżeli masz jakiekolwiek pytania lub chcesz zgłosić błąd zapraszamy do kontaktu z naszym [wsparciem technicznym][ext13].**

## Wymagania

**Ważne:** Moduł działa tylko z punktem płatności typu `REST API`.

Jeżeli nie posiadasz jeszcze konta w systemie PayU [**zarejestruj się w systemie produkcyjnym**][ext4] lub [**zarejestruj się w systemie sandbox**][ext5]

Do prawidłowego funkcjonowania modułu wymagane są następujące rozszerzenia PHP: [cURL][ext1] i [hash][ext2].

## Instalacja
Skorzystaj z [automatycznej instalacji oraz aktywacji](https://wordpress.org/support/article/managing-plugins/#automatic-plugin-installation) dostępnej w panelu administracyjnym Wordpress. Nazwa wtyczki PayU w repozytorium wtyczek to `WooCommerce PayU EU Payment Gateway`

## Metody płatności
Wtyczka udostępnia następujące metody płatności:

| Lp    | Metoda | Opis
| :---: | ------ | ---
| 1     | PayU - standard         | Płacący zostanie przekierowany na stronę PayU gdzie wybierze typ płatności (z listy typów dostępnych na punkcie płatności)
| 2     | PayU - lista banków     | Wyświetlana jest lista typów płatności, a płacący w zależności od wybranego typu zostanie przekierowany do banku lub na stronę PayU
| 3     | PayU - karta płatnicza  | Płacący zostanie przekierowany na stronę PayU gdzie wprowadzi dane karty (kredytowej, debetowej lub prepaid)
| 4     | PayU - secure form      | Wyświetlany jest formularz wprowadzania danych karty
| 5     | PayU - Blik             | Płacący zostanie przekierowany na stronę Blik
| 6     | PayU - raty             | Płacący zostanie przekierowany na stronę formularza płatności ratalnej

#### Uwagi do metod płatności

* Metody `PayU - standard` i `PayU - lista banków` umożliwiają płatność dowolnym typem płatności, a różnią się tylko miejscem jego wyboru. **Nie powinny być razem włączone**.
* Metody `PayU - karta płatnicza` i `PayU - secure form` umożliwiają płatność kartą, a różnią się tylko miejscem wprowadzenia danych karty. **Nie powinny być razem włączone**.
* W przypadku gdy jest włączona metoda `PayU - lista banków` z listy dostępnych typów płatności usuwane są: karty, gdy włączona jest metoda `PayU - karta kredytowa` lub `PayU - secure form`, Blik, gdy włączona jest metoda `PayU - blik`, raty gdy włączona jest metoda `PayU - raty`.
* Metoda `PayU - secure form` wymaga aby sklep był dostępny za pomocą połączenia HTTPS (przy testach lokalnych adres strony powinien być http://localhost)
* Pomimo włączenia metod  `PayU - karta kredytowa`, `PayU - secure form`, `PayU - blik` i `PayU - raty` mogą się one nie pokazać płącącemu, jeśli dany typ płatności nie jest włączony na punkcie płatności lub jeśli kwota nie mieści się między kwotą minimalną i maksymalną dla danego typu.

## Konfiguracja
#### Konfiguracja globalna
Jest dostępna w głównym menu WooCommerce jako `Ustawienia PayU`

Parametry punktu płatności:

| Parametr | Opis
| --------- | ----
| Id punktu płatności| Identyfikator punktu płatności z systemu PayU
| Drugi klucz MD5 | Drugi klucz MD5 z systemu PayU
| OAuth - client_id | client_id dla protokołu OAuth z systemu PayU
| OAuth - client_secret | client_secret dla protokołu OAuth z systemu PayU
| Sandbox - Id punktu płatności| Identyfikator punktu płatności z systemu Sandbox PayU
| Sandbox - Drugi klucz MD5 | Drugi klucz MD5 z systemu Sandbox Sandbox PayU
| Sandbox - OAuth - client_id | client_id dla protokołu OAuth z systemu Sandbox PayU
| Sandbox - OAuth - client_secret | client_secret dla protokołu OAuth z systemu Sandbox PayU

* W przypadku korzystania z pluginu `WPML` dla każdej waluty będzie dostępna osobna konfiguracja punktu płatności
* Domyślnie każda z metod płatności korzysta z globalnych parametrów punktu płatności

Inne parametry - mają zastosowanie do wszystkich modułów:

| Parametr | Opis
| --------- | ----
| Domyślny status zamówienia | Status w jaki przejdzie zamówienie po rozpoczęciu płatności. Możliwe wartości to `Wstrzymane (oczekujące na płatność) - on-hold` i `Oczekujące na płatność - pending`
| Włącz ponawianie płatności | Umożliwia płacącemu ponowienie nieudanej płatności. Przed włączeniem proszę o zapoznanie się z rozdziałem [Ponawianie płatności](#ponawianie-płatności).

#### Konfiguracja metod płatności
Parametry, które są dostępne dla każdej metody płatności:

| Parametr | Opis
| --------- | ----
| Włącz / Wyłącz  | Włącza metodę płatności
| Nazwa | Nazwa metody wyświetlana na stronie wyboru metody płatności
| Tryb sandbox | W przypadku włączenia konfiguracja oraz płatności wykonywane są na środowisku Sandbox
| Użyj wartości globalnych | W przypadku włączenia używane są globalne parametry punktu płatności, w przeciwnym wypadku należy wprowadzić parametry
| Opis | Opis metody wyświetlany po wybraniu jej na stronie wyboru metody płatności
| Włącz dla metod wysyłki | Możliwość wyboru dla jakich metod wysyłki będzie dostępna metoda płatności. W przypadku gdy nie wybierzemy żadnej metody wysyłki to metoda płatności jest dostępna dla wszystkich metod wysyłki.

Parametry, które są dodatkowo dostępne dla metody płatności `PayU - lista banków`:

| Parametr | Opis |
| --------- | ---- |
| Własna kolejność | W celu ustalenia kolejności wyświetlanych ikon typów płatności należy podać ich symbole oddzielając je przecinkiem. [Lista typów płatności][ext6].
| Pokaż nieaktywne metody płatności | W przypadku włączenia, gdy dany typ płatności jest nieaktywny pokazuje się na liście jako wyszarzony, w przeciwnym razie nie jest w ogóle pokazywany

## Ponawianie płatności
Dzięki tej opcji kupujący otrzymuje możliwość skutecznego opłacenia zamówienia, nawet jeśli pierwsza płatność była nieudana (np. brak środków na karcie, problemy z logowaniem do banku itp.).

Aby użyć tej opcji, należy również odpowiednio skonfigurować punkt płatności w PayU i wyłączyć automatycznie odbieranie płatności (domyślnie auto-odbiór jest włączony). W tym celu należy zalogować się do panelu PayU, wejść do zakładki "Płatności elektroniczne", następnie wybrać "Moje sklepy" i punkt płatności na danym sklepie. Opcja "Automatyczny odbiór płatności" znajduje się na samym dole, pod listą typów płatności.

Ponowienie płatności umożliwia zakładanie wielu płatności w PayU do jednego zamówienia w WooCommerce. Wtyczka automatycznie odbierze pierwszą udaną płatność, a pozostałe zostaną anulowane. Ponowienie płatności przez kupującego jest możliwe:
* poprzez kliknięcie w link znajdujący się w mailu potwierdzającym zamówienie
* z listy zamówień po kliknięciu w link "Zapłać z PayU" w kolumnie Akcje
* w szczegółach zamówienia poprzez kliknięcie w link "Zapłać z PayU" znajdujący się nad "Szczegóły zamówienia"

## Maile
Plugin nie wysyła żadnych dodatkowych maili. Nie ingeruje również w proces, kiedy maile są wysyłane.

W przypadku włączonego ponownienia płatności do maila potwierdzajacego zamówienia dodawana jest informacja o możliwości wykonania płatności: `Jeżeli jeszcze nie opłaciłeś zamówienia możesz to zrobić przechodzą na stronę.`

<!--external links:-->
[ext1]: http://php.net/manual/en/book.curl.php
[ext2]: http://php.net/manual/en/book.hash.php
[ext4]: https://www.payu.pl/oferta-handlowa
[ext5]: https://secure.snd.payu.com/boarding/#/registerSandbox/?lang=pl
[ext6]: http://developers.payu.com/pl/overview.html#paymethods
[ext13]: https://www.payu.pl/pomoc
