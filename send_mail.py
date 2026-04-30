import sys
import smtplib
from email.mime.text import MIMEText

EMAIL = "denverbentulan87@gmail.com"
PASSWORD = "iqiu utii nuyk hinz"

to_email = sys.argv[1]

msg = MIMEText("You were marked absent today.")
msg['Subject'] = "Attendance Alert"
msg['From'] = EMAIL
msg['To'] = to_email

with smtplib.SMTP_SSL('smtp.gmail.com', 465) as server:
    server.login(EMAIL, PASSWORD)
    server.send_message(msg)