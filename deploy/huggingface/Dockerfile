# Hugging Face Spaces image (Docker SDK).
# Spaces run containers as user 1000 and expect the app on port 7860, so this
# uses PHP's built-in server with router.php instead of Apache.
FROM php:8.3-cli

RUN useradd -m -u 1000 user
WORKDIR /app
COPY --chown=user . /app
USER user

ENV DB_DRIVER=sqlite \
    DB_SQLITE_PATH=/tmp/payroll.sqlite \
    DEMO_MODE=1 \
    SESSION_SAMESITE=None \
    PHP_CLI_SERVER_WORKERS=4

EXPOSE 7860
CMD ["php", "-S", "0.0.0.0:7860", "-t", "/app", "/app/router.php"]
