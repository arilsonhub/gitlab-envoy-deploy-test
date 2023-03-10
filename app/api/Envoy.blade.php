@servers(['stage' => 'deployer@acme-stage'])

@setup
    $repository = 'http://gitlab-ci-token:' . $ci_token . '@gitlab-server/office/acme.git';
    $releases_dir = '/opt/acme/releases';
    $app_dir = '/opt/acme';
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
    remove_previous_releases
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
@endtask

@task('update_release_symlinks')
    echo "Linking API storage directory"
    rm -rf {{ $api_new_release_storage_dir }}
    ln -nfs {{ $common_api_storage_dir }} {{ $api_new_release_storage_dir }}

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }}/* {{ $app_current_release_dir }}
@endtask

@task('remove_previous_releases')
    echo "Removing previous releases"
    cd {{ $releases_dir }}
    FILES=`ls -d * | grep -v {{ $release }}`
    for FILE in $FILES
    do
        echo "Removing $FILE..."
        rm -rf $FILE
    done
@endtask
