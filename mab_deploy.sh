#!/bin/bash

# MyAgBuddy Deploy Script
# v0.1 - Fritz Bester <fritz@liquidfibre.com>
# 2021

prod_url='root@myagbuddy.com';
ssh_id='-i ~/.ssh/fritzb_deploy';
dev_db_name='agri';
prod_db_name='agri';
db_pass='Liquidfib$865!';

opt_clean=false;
opt_code=false;
opt_replace=false;
opt_migrate=false;

display_help(){
    echo "MyAgBuddy Deploy Script v0.1\n";
    echo "Usage: mab_deploy.sh [ clean | code | migrate | replace]\n";
    echo "Options:\n";
    echo "clean   - Cleans deployment files on live\n";
    echo "code    - Deploy and build only the code (frontend and backend)\n";
    echo "migrate - Run Laravel's Migrate after deployment\n";
    echo "replace - Completely overwrite the live DB with the staging DB\n";
    exit 0;
}

clean_deploy(){
    printf "Cleaning up...."
    ssh $ssh_id $prod_url '
    rm -rf /var/www/api_new;
    rm -rf /var/www/api_prev;
    rm -rf /var/www/html_new;
    rm -rf /var/www/html_prev;
    rm -f /var/www/mab*.*;
    rm -f /var/www/import/mab_build_in_progress;';
    printf "DONE\n"
}

get_db(){
    printf "Exporting DB...."
    cd /var/www

    mysqldump -u root -p$db_pass $dev_db_name > mab_deploy_db_export.sql
    if [ $? -ne 0 ]; then echo "Error exporting development database"; exit 1; fi

    zip -rm mab_deploy_db_export.zip mab_deploy_db_export.sql
    if [ ! -f 'mab_deploy_db_export.zip' ]; then echo "Error creating mab_deploy_db_export.zip"; exit 1; fi
    
    printf "DONE\n"
}

get_files(){
    printf "Creating Archives...."
    cd /var/www

    (cd api && zip -q -x "vendor/*" "composer.lock" "mab_deploy.sh" -r ../mab_deploy_api_export.zip . )
    if [ $? -ne 0 ]; then echo "Error creating mab_deploy_api_export.zip"; exit 1; fi

    (cd html && zip -q -x "vendor/*" "node_modules/*" "package-lock.json" -r ../mab_deploy_html_export.zip . )
    if [ $? -ne 0 ]; then echo "Error creating mab_deploy_html_export.zip"; exit 1; fi

    printf "DONE\n"
}

send_files(){
    printf "Transferring Archives...."
    cd /var/www

    scp $ssh_id mab_deploy_api_export.zip $prod_url:/var/www
    if [ $? -ne 0 ]; then echo "Error transferring mab_deploy_api_export.zip"; exit 1; fi

    scp $ssh_id mab_deploy_html_export.zip $prod_url:/var/www
    if [ $? -ne 0 ]; then echo "Error transferring mab_deploy_html_export.zip"; exit 1; fi

    scp $ssh_id mab_deploy_db_export.zip $prod_url:/var/www
    if [ $? -ne 0 ]; then echo "Error transferring mab_deploy_db_export.zip"; exit 1; fi

    printf "DONE\n"
}

prep_files(){
    printf "Preparing Files...."

    ssh $ssh_id $prod_url 'unzip -q -d /var/www/api_new /var/www/mab_deploy_api_export.zip'
    if [ $? -ne 0 ]; then echo "Error extracting mab_deploy_api_export.zip"; exit 1; fi

    ssh $ssh_id $prod_url 'unzip -q -d /var/www/html_new /var/www/mab_deploy_html_export.zip'
    if [ $? -ne 0 ]; then echo "Error extracting mab_deploy_html_export.zip"; exit 1; fi
    
    ssh $ssh_id $prod_url 'sed -i "s/devapi/api/g" /var/www/html_new/src/store/store.js'
    if [ $? -ne 0 ]; then echo "Error replacing text in html_new/src/store/store.js"; exit 1; fi

    ssh $ssh_id $prod_url 'sed -i "s/devapi/api/g" /var/www/api_new/.env'
    if [ $? -ne 0 ]; then echo "Error replacing text in api_new/.env"; exit 1; fi

    ssh $ssh_id $prod_url 'cd /var/www; unzip -qq mab_deploy_db_export.zip; rm -f mab_deploy_db_export.zip'
    ssh $ssh_id $prod_url 'if [ ! -f "/var/www/mab_deploy_db_export.sql" ]; then exit 1; fi'

    if [ $? -ne 0 ]; then echo "Error extracting 'mab_deploy_db_export.zip'"; exit 1; fi

    printf "DONE\n"
}

build_backend(){
    printf "Building Backend...."
    ssh $ssh_id $prod_url 'cd /var/www/api_new; composer install --no-dev --optimize-autoloader'
    if [ $? -ne 0 ]; then echo "Error on backend composer install"; exit 1; fi

    ssh $ssh_id $prod_url 'cd /var/www/api_new; php artisan cache:clear; php artisan config:cache'

    printf "DONE\n"
}

build_frontend(){
    printf "Building Frontend...."
    ssh $ssh_id $prod_url 'cd /var/www/html_new; composer install'
    if [ $? -ne 0 ]; then echo "Error on frontend composer install"; exit 1; fi

    ssh $ssh_id $prod_url 'cd /var/www/html_new; php artisan cache:clear; php artisan config:cache'

    ssh $ssh_id $prod_url 'cd /var/www/html_new; npm install'
    if [ $? -ne 0 ]; then echo "Error installing node.js"; exit 1; fi

    ssh $ssh_id $prod_url 'cd /var/www/html_new; npm run build'
    if [ $? -ne 0 ]; then echo "Error building frontend"; exit 1; fi

    printf "DONE\n"
}

replace_db(){
    printf "Importing DB...."
    ssh $ssh_id $prod_url 'touch /var/www/import/mab_build_in_progress; cd /var/www/html; php artisan down; cd /var/www/api; php artisan down'
    ssh $ssh_id $prod_url 'if [ ! -f "/var/www/import/mab_build_in_progress" ]; then exit 1; fi'
    if [ $? -ne 0 ]; then echo "Error creating mab_build_in_progress lock file"; exit 1; fi

    ssh $ssh_id $prod_url "mysql -u root -p'$db_pass' $prod_db_name < /var/www/mab_deploy_db_export.sql"
    if [ $? -ne 0 ]; then echo "Error importing DB"; exit 1; fi

    printf "DONE\n"
}

migrate_db(){
    printf "Migrating DB...."
    ssh $ssh_id $prod_url 'cd /var/www/html; php artisan down; cd /var/www/api; php artisan down'

    ssh $ssh_id $prod_url 'cd /var/www/api_new; php artisan migrate --force'
    if [ $? -ne 0 ]; then echo "Error migrating DB"; exit 1; fi

    printf "DONE\n"
}

roll_over(){
    printf "Finalizing deployment...."

    ssh $ssh_id $prod_url 'apachectl stop'; 

    ssh $ssh_id $prod_url 'cd /var/www; if [ -d "api_prev" ]; then rm -rf api_prev; fi';
    ssh $ssh_id $prod_url 'cd /var/www; if [ -d "html_prev" ]; then rm -rf html_prev; fi';
    ssh $ssh_id $prod_url 'cd /var/www; if [ -d "api" ]; then mv api api_prev; fi';
    ssh $ssh_id $prod_url 'cd /var/www; if [ -d "html" ]; then mv html html_prev; fi';
    ssh $ssh_id $prod_url 'cd /var/www; mv api_new api';
    ssh $ssh_id $prod_url 'cd /var/www; mv html_new html';
    ssh $ssh_id $prod_url 'cd /var/www; rm -f import/mab_build_in_progress';

    ssh $ssh_id $prod_url 'chgrp apache /var/www/api'; 
    if [ $? -ne 0 ]; then echo "Error changing 'api' folder group"; exit 1; fi

    ssh $ssh_id $prod_url 'chown -R apache: /var/www/api'; 
    if [ $? -ne 0 ]; then echo "Error changing 'api' folder owner"; exit 1; fi

    ssh $ssh_id $prod_url 'find /var/www/api -type f -exec chmod 664 {} + -o -type d -exec chmod 775 {} +';
    if [ $? -ne 0 ]; then echo "Error changing 'api' folder and file permissions"; exit 1; fi

    ssh $ssh_id $prod_url 'chmod g+s /var/www/api';
    if [ $? -ne 0 ]; then echo "Error changing 'api' file permission inheritance"; exit 1; fi

    ssh $ssh_id $prod_url 'chmod -R guo+w /var/www/api/storage';
    if [ $? -ne 0 ]; then echo "Error adding 'api' writable permissions"; exit 1; fi


    ssh $ssh_id $prod_url 'chgrp apache /var/www/html'; 
    if [ $? -ne 0 ]; then echo "Error changing 'html' folder group"; exit 1; fi

    ssh $ssh_id $prod_url 'chown -R apache: /var/www/html'; 
    if [ $? -ne 0 ]; then echo "Error changing 'html' folder owner"; exit 1; fi

    ssh $ssh_id $prod_url 'find /var/www/html -type f -exec chmod 664 {} + -o -type d -exec chmod 775 {} +';
    if [ $? -ne 0 ]; then echo "Error changing 'html' folder and file permissions"; exit 1; fi

    ssh $ssh_id $prod_url 'chmod g+s /var/www/html';
    if [ $? -ne 0 ]; then echo "Error changing 'html' file permission inheritance"; exit 1; fi

    ssh $ssh_id $prod_url 'chmod -R guo+w /var/www/html/storage';
    if [ $? -ne 0 ]; then echo "Error adding 'html' writable permissions"; exit 1; fi

    ssh $ssh_id $prod_url 'apachectl start'; 

    ssh $ssh_id $prod_url 'cd /var/www/html; php artisan cache:clear; php artisan config:cache';
    ssh $ssh_id $prod_url 'cd /var/www/api; php artisan cache:clear; php artisan config:cache';

    mv /var/www/mab_deploy_api_export.zip /var/www/mab_deploy_api_export_prev.zip &> /dev/null
    mv /var/www/mab_deploy_html_export.zip /var/www/mab_deploy_html_export_prev.zip &> /dev/null
    mv /var/www/mab_deploy_db_export.sql /var/www/mab_deploy_db_export_prev.sql &> /dev/null

    printf "DONE!\n"
}

# main

i=0;
for arg in "$@" 
do
    if [ "$arg" == "clean" ]; then opt_clean=true; fi;
    if [ "$arg" == "code" ]; then opt_code=true; fi;
    if [ "$arg" == "migrate" ]; then opt_migrate=true; fi;
    if [ "$arg" == "replace" ]; then opt_replace=true; fi;
    i=$((i+1));
done

if [ $i == 0 ]; then display_help; fi;
if [ "$opt_clean" == true ]; then clean_deploy; fi
if [ "$opt_code"  == true ]; then 
    get_db;
    get_files;
    send_files;
    prep_files;
    build_backend;
    build_frontend;
fi;

if [ "$opt_migrate" == true ]; then migrate_db; fi
if [ "$opt_replace" == true ]; then replace_db; fi

if [ "$opt_code" == true ] || [ "$opt_migrate" == true ] || [ "$opt_replace" == true ]; then
    roll_over
fi;

printf "\nDONE\n"