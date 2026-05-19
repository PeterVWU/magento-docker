# Copy the relevant values into a local backend config when remote state is ready.
# Do not commit environment-specific backend files containing real bucket names
# unless the bucket naming has been reviewed.

bucket = "YOUR-TOFU-STATE-BUCKET"
prefix = "magento/ecom-6/ENVIRONMENT"
