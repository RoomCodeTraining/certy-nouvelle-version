# OCR pour PDF scannés : Imagick, Ghostscript, Tesseract
RUN (apt-get update || true) && DEBIAN_FRONTEND=noninteractive apt-get install -y -o Dpkg::Options::="--force-confnew" --no-install-recommends \
    ghostscript \
    tesseract-ocr \
    tesseract-ocr-fra \
    tesseract-ocr-eng \
    php${DDEV_PHP_VERSION}-imagick \
    && rm -rf /var/lib/apt/lists/*
