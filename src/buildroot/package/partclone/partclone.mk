################################################################################
#
# partclone
#
################################################################################

PARTCLONE_VERSION = 0.2.78
PARTCLONE_SOURCE = partclone_$(PARTCLONE_VERSION).orig.tar.gz
PARTCLONE_SITE = http://www.mirrorservice.org/sites/downloads.sourceforge.net/p/pa/partclone/testing/src
PARTCLONE_INSTALL_STAGING = YES
PARTCLONE_AUTORECONF = YES
PARTCLONE_DEPENDENCIES = attr e2fsprogs libgcrypt lzo xz zlib xfsprogs ncurses host-pkgconf
PARTCLONE_CONF_OPTS = --enable-static --enable-xfs --enable-btrfs --enable-ntfs --enable-extfs --enable-fat --enable-hfsp --enable-ncursesw

define PARTCLONE_LINK_LIBRARIES_TOOL
    ln -f -s $(BUILD_DIR)/xfsprogs-*/include/xfs $(STAGING_DIR)/usr/include/
    ln -f -s $(BUILD_DIR)/xfsprogs-*/libxfs/.libs/libxfs.* $(STAGING_DIR)/usr/lib/
endef

PARTCLONE_POST_PATCH_HOOKS += PARTCLONE_LINK_LIBRARIES_TOOL

$(eval $(autotools-package))
