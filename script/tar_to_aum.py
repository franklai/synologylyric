#!/usr/bin/env python3
from contextlib import contextmanager
import json
import os
import shutil
import sys
import tarfile

extension_name = "aum"
module_root = "../modules"
archive_root = "../archive"
include_root = "../include"
info_file = "INFO"
common_file = "fujirou_common.php"


@contextmanager
def cwd(path):
    oldpwd = os.getcwd()
    os.chdir(path)
    try:
        yield
    finally:
        os.chdir(oldpwd)


def tar_reset(tarinfo):
    tarinfo.uid = tarinfo.gid = 99
    tarinfo.uname = tarinfo.gname = "nobody"
    return tarinfo


def do_tar(module_name):
    module_dir = os.path.join(module_root, module_name)
    info_path = os.path.abspath(os.path.join(module_dir, info_file))

    if not os.path.exists(info_path):
        logging.error("INFO file does not exists. {}".format(info_path))
        return False

    text = open(info_path, "rb").read()
    obj = json.loads(text)

    # key exists check
    if "module" not in obj or "version" not in obj:
        return False

    module_dir = os.path.join(module_root, module_name)
    module_file = obj["module"]
    php_path = os.path.abspath(os.path.join(module_dir, module_file))

    if not os.path.exists(php_path):
        logging.error("PHP file does not exists. {}".format(php_path))
        return False

    module_version = obj["version"]
    archive_file = "%s-%s.%s" % (module_name, module_version, extension_name)
    archive_path = os.path.abspath(os.path.join(archive_root, archive_file))

    files_to_be_archived = [info_file, module_file]

    common_path = os.path.abspath(os.path.join(include_root, common_file))
    common_temp = os.path.abspath(os.path.join(module_dir, common_file))
    common_exists = os.path.exists(common_path)
    if common_exists:
        files_to_be_archived.append(common_file)
        shutil.copyfile(common_path, common_temp)
    with cwd(module_dir):
        print("archive_path is: {}".format(archive_path))
        with tarfile.open(archive_path, "w:gz") as tar:
            for name in files_to_be_archived:
                print("gonna add name: {}".format(name))
                tar.add(name, filter=tar_reset)

    if common_exists:
        os.unlink(common_temp)


def print_usage():
    print("%s [module_name]\n" % (os.path.basename(__file__)))

    sys.exit(0)


if __name__ == "__main__":
    argv = sys.argv

    if len(argv) < 2:
        print_usage()

    module_name = os.path.basename(os.path.normpath(argv[1]))

    do_tar(module_name)
