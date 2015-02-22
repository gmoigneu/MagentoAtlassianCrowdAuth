# MagentoAtlassianCrowdAuth

Magento 1.x module to authenticate backend users through Atlassian Crowd

## Setup

- Install with modman
- Create a new Application in Crowd dedicated to Magento
- Configure the module in System > Config > Crowd Authentification

** For now, your Magento admin group must be named "Administrators" (default)

## TODO's & restriction

- Magento local accounts can't login anymore
- All authenticated users are logged in as Administrators
- Deleted users in Crowd are kept inactive in Magento

## Credits

The authenticating method is based on the [LoginProviderFramework](https://github.com/magento-hackathon/LoginProviderFramework) from [magento-hackathon](https://github.com/magento-hackathon)

