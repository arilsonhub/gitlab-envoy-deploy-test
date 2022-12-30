@servers(['stage' => 'deployer@product-stage'])

@setup
    $repository = 'http://gitlab-ci-token:' . $ci_token . '@gitlab-server/gitlab-instance-151284ce/product-acme.git';
    $apache_web_dir = '/var/www/html';
    $apache_api_public_dir = '/var/www/html1/public';
    $releases_dir = '/opt/product/releases';
    $app_dir = '/opt/product';
    $release = date('YmdHis');
    $common_web_env_dir = $app_dir . '/common/web/.env';
    $common_api_env_dir = $app_dir . '/common/api/.env';
    $new_release_dir = $releases_dir .'/'. $release;
    $api_new_release_dir = $new_release_dir . '/app/api';
    $web_new_release_dir = $new_release_dir . '/app/web';
    $api_new_release_env_file = $api_new_release_dir . '/.env';
    $web_new_release_env_file = $web_new_release_dir . '/.env';
    $api_new_release_storage_dir = $api_new_release_dir . '/storage';
    $common_api_storage_dir = $app_dir . '/storage';
    $app_current_release_dir = $app_dir . '/current';
@endsetup

@story('deploy-stage', ['on' => 'stage'])
    clone_repository
    build_api
    build_web
    update_release_symlinks
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --single-branch --branch {{ $branch }} --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('build_api')
    echo "Starting API deployment ({{ $release }})"
    cd {{ $api_new_release_dir }}
    echo 'Linking API .env file'
    ln -nfs {{ $common_api_env_dir }} {{ $api_new_release_env_file }}
    echo "Starting API Build"
    composer install --no-dev --prefer-dist --no-scripts -o
    composer install --prefer-dist --no-scripts -o
    echo "API - Removing cache dir"
    rm -rf bootstrap/cache
    echo "API - Recreating cache dir"
    mkdir bootstrap/cache
    echo "API - Clear cache config"
    php artisan config:clear
@endtask

@task('build_web')
    echo "Starting Web deployment ({{ $release }})"
    cd {{ $web_new_release_dir }}
    echo 'Linking Web .env file'
    ln -nfs {{ $common_web_env_dir }} {{ $web_new_release_env_file }}
    echo 'Building node modules'
    npm install
    echo "Starting Web build"
    npm run build
    echo "remove current build"
    rm -rf {{ $apache_web_dir }}/*
    echo "move new build files"
    mv dist/* {{ $apache_web_dir }}
@endtask

@task('update_release_symlinks')
    echo "Linking API storage directory"
    rm -rf {{ $api_new_release_storage_dir }}
    ln -nfs {{ $common_api_storage_dir }} {{ $api_new_release_storage_dir }}

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }}/* {{ $app_current_release_dir }}

    echo 'Linking apache API document root'
    ln -nfs {{ $api_new_release_dir }}/public/* {{ $apache_api_public_dir }}

    echo 'Linking API .htaccess'
    ln -nfs {{ $api_new_release_dir }}/public/.htaccess {{ $apache_api_public_dir }}/.htaccess
@endtask
