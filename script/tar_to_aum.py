import json
import os
import shutil
import sys

extension_name = 'aum'
module_root = '../modules'
archive_root = '../archive'
include_root = '../include'
info_file = 'INFO'
common_file = 'fujirou_common.php'


def do_tar(module_name):
    module_dir = os.path.join(module_root, module_name)
    info_path = os.path.join(module_dir, info_file)
    common_path = os.path.join(include_root, common_file)
    common_temp = os.path.join(module_dir, common_file)

    if not os.path.exists(info_path):
        return False

    text = open(info_path, 'rb').read()
    obj = json.loads(text)

    # key exists check
    if 'module' not in obj or 'version' not in obj:
        return False

    module_dir = os.path.join(module_root, module_name)
    module_file = obj['module']
    php_path = os.path.join(module_dir, module_file)

    cmd = 'ls %s %s' % (info_path, php_path)
    os.system(cmd)

    module_version = obj['version']
    archive_file = '%s-%s.%s' % (module_name, module_version, extension_name)
    archive_path = os.path.join(archive_root, archive_file)
    
    files_to_be_archived = [info_file, module_file]

    common_exists = os.path.exists(common_path)
    if common_exists:
        files_to_be_archived.append(common_file)

        shutil.copyfile(common_path, common_temp)

        st = os.stat(common_path)
        os.chown(common_temp, st.st_uid, st.st_gid)
        os.chmod(common_temp, st.st_mode)

    cmd = 'COPYFILE_DISABLE=1 tar zcvf %s -C %s %s' % (archive_path, module_dir, ' '.join(files_to_be_archived))
    os.system(cmd)

    if common_exists:
        os.unlink(common_temp)

def print_usage():
    print('%s [module_name]\n' % (os.path.basename(__file__)))

    sys.exit(0)

if __name__ == '__main__':
    argv = sys.argv

    if len(argv) < 2:
        print_usage()

    module_name = os.path.basename(os.path.normpath(argv[1]))

    do_tar(module_name)
