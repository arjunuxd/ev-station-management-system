Setup Instructions
1. Import the database
bashmysql -u root -p < sql/ev_station_db.sql
2. Configure DB credentials in config/database.php — update DB_USER, DB_PASS, and BASE_URL to match your server.
3. Demo credentials (already seeded):
Demo Credentials
Admin: admin@ev.com / admin123
User: john@example.com / user123

What's included
Admin workflow → Dashboard with live stats → Stations (add/edit/delete via modal) → Connectors per station (filter by station) → All Bookings (filter by status/date/station, update status inline) → Users (toggle role, delete)
User workflow → Station list with live availability chips → Search by name/location → Book a slot (connector selector, date/time, conflict check) → My Bookings (tabbed by status: upcoming/active/completed/cancelled, cancel with confirm, rebook shortcut) → Cancel booking with confirmation page