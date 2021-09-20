# Local design system

This docker-compose set up will allow you to `yarn link` to the opendialog design system for local development

## Instructions

1. Checkout the OpenDialog app (this) and the OpenDialog Design system so that they are at the same level:

dir
|_ opendialog
|_ opendialog design system

2. Follow the steps in the readme.md file here to get a local version of OpenDialog running.

3. Once running, you can link the two projects by following these steps:

- CD into the od-docker-demo directory
- make sure the project is running with `docker-compose up -d`
- Connect to the docker container by running `docker-compose exec app bash`
- You should now ben connected to the docker container at `/var/www`. If you run `ls` you should see the OD app and a directory named `opendialog-design-system`
- `cd opendialog-design-system` - change to the design system repo
- `yarn link` - link the project 
- `cd ../` change back to the main app dir
- `yarn link @opendialogai/opendialog-design-system-pkg` - link into this project
- `yarn watch` - this will now watch all changes in your local design system and rebuild them into the docker project