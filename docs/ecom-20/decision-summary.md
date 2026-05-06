# ECOM-20 Decision Summary

## Owner Decision Rule

- `Keep`: include the local module or Composer package in the new Magento build as-is for now.
- `Remove`: do not include the local module or Composer package in the new Magento build.

## Local Modules

Source: [legacy-local-modules.csv](legacy-local-modules.csv)

| Owner decision | Count |
| --- | ---: |
| Keep | 104 |
| Remove | 62 |
| Total | 166 |

## Composer Packages

Source: [legacy-composer-requirements.csv](legacy-composer-requirements.csv)

| Owner decision | Count |
| --- | ---: |
| Keep | 67 |
| Remove | 0 |
| Total | 67 |

## Remaining Follow-Up

- Kept private Composer packages still need clean-room credential handling through ECOM-59 before installation in the new Magento build.
- Removed local modules should not be copied into `magento-modern`.
- Raw production config and secrets must remain outside the repository.
