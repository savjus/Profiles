# Profiles local development

## Run with Docker Compose

```sh
docker compose up --build
```

Open the application at <http://localhost:8080>.

!!!!! IN ./include/constants.php SET
define("DB_SERVER", "db");
TO WORK WITH DOCKER
!!!!!!!!!!!!!

phpMyAdmin is available at <http://localhost:8081>:

- Server: `db`
- Username: `stud`
- Password: `stud`
- Database: `social`

## Seeded development data

The Compose database initialization runs `db.sql` followed by `seed.sql` when
the `mysql_data` volume is created. The seed creates users, posts, comments,
follows, reactions, and messages.

All seeded users use the password `demo`:

| Username                           | Role          |
| ---------------------------------- | ------------- |
| `Admin`                            | Administrator |
| `Moderator`                        | Moderator     |
| `Aiste`, `Jonas`, `Greta`, `Tomas` | User          |

To apply the seed to an existing database:

```sh
docker compose exec -T db mysql -ustud -pstud social < seed.sql
```

The inserts are idempotent and can be run more than once.

To reset the database and run both initialization files again:

```sh
docker compose down -v
docker compose up --build
```
