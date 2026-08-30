# Changelog

## 1.0.0 (2026-08-30)

Initial release for Moodle 5.2.

- Browse activity embedding external content (iframe or new window) with
  mod_url-style URL variable substitution on the main URL and step URLs.
- Teacher-defined steps with three completion mechanisms: manual check-off,
  visiting a step link, and a return link the external site redirects to.
- Optional sequential mode locking later steps until earlier ones are done.
- Custom activity completion condition "Complete all steps".
- Manage steps page and step progress report with group filtering.
- Backup/restore including user progress, course reset, privacy API,
  events, PHPUnit and Behat coverage, GitHub Actions CI.
