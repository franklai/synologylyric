#!/usr/bin/env python3
import json
import os
import sys
import tarfile

extension_name = 'aum'
current_dir = os.path.dirname(__file__)
archive_root = os.path.join(current_dir, '..', 'archive')
src_root = os.path.join(current_dir, '..', 'src')
common_file = 'FujirouCommon.php'
info_file = 'INFO'


def do_tar(info_path):
    module_dir = os.path.dirname(info_path)
    common_path = os.path.join(module_dir, common_file)

    if not os.path.exists(info_path):
        return False

    text = open(info_path, 'rb').read()
    obj = json.loads(text)

    # key exists check
    if 'module' not in obj or 'version' not in obj:
        return False

    module_name = obj['name']
    module_file = obj['module']
    module_path = os.path.join(module_dir, module_file)

    module_version = obj['version']
    archive_file = '%s-%s.%s' % (module_name, module_version, extension_name)
    archive_path = os.path.abspath(os.path.join(archive_root, archive_file))

    with tarfile.open(archive_path, 'w:gz') as tar:
        tar.add(module_path, arcname=module_file)
        tar.add(info_path, arcname=info_file)
        tar.add(common_path, arcname=common_file)

        print('create archive {}'.format(archive_path))
        print('including:')
        print('\t{} - {}'.format(module_file, module_path))
        print('\t{} - {}'.format(info_file, info_path))
        print('\t{} - {}'.format(common_file, common_path))

def print_usage():
    print('%s [module json path]\n' % (os.path.basename(__file__)))

    sys.exit(0)

if __name__ == '__main__':
    argv = sys.argv

    if len(argv) < 2:
        print_usage()
        sys.exit(-1)

    json_path = argv[1]
    if not os.path.exists(json_path):
        print('path {} not exists'.format(json_path))
        sys.exit(-1)

    do_tar(os.path.abspath(json_path))
