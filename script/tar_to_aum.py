import json
import os
import sys

module_root = '../modules'
archive_root = '../archive'

def do_tar(module_name):
    info_path = os.path.join(module_root, module_name, 'INFO')

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
    archive_file = '%s-%s.aum' % (module_name, module_version)
    archive_path = os.path.join(archive_root, archive_file)

    cmd = 'COPYFILE_DISABLE=1 tar zcvf %s -C %s  INFO %s' % (archive_path, module_dir, module_file)
    os.system(cmd)

def print_usage():
    print('%s [module_name]\n' % (os.path.basename(__file__)))

    sys.exit(0)

if __name__ == '__main__':
    argv = sys.argv

    if len(argv) < 2:
        print_usage()

    module_name = os.path.basename(os.path.normpath(argv[1]))

    do_tar(module_name)
